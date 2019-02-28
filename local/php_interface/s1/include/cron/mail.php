#!/usr/bin/php
<?
// ������� 2�
//
// �� ����� ������ ��� ������� ������ ������ ����� �������� ������������� ����� �� cron,
// �� ��� � ������� ������� �������� ��� ������ ��� ������� �� �������, �� ������ ����� �������

// ��� ������� �� ������� ����� ���� ���������� DOCUMENT_ROOT
// � ����� ������������ ������������� ������� �����������, �� ����� ��������� ��������
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../../../../..');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

@set_time_limit(0); // ������� ����������� �� �������, �.�. ������� ����� ���� ����������

use Bitrix\Sale\Order;

// ��������! $_SERVER['DOCUMENT_ROOT'] ����� ��� ���������� �������� ������� Loader-��
if (!Bitrix\Main\Loader::includeModule('sale'))
{
    return;
}

$date = new Bitrix\Main\Type\DateTime(); // ������� ����

// �������� ������, ��������� �������������� �� ��������� �����
$orderItemsList = [];
$orderIterator  = Order::getList([
    'filter' => [
        'CANCELED'      => 'N',
        '>=DATE_INSERT' => $date->add('- 1 month') // �������� 1 �����
    ],
    'select' => ['PERSON_TYPE_ID', 'ID']
]);
while ($order = $orderIterator->fetch())
{
    $orderBasketItems = Order::load($order['ID'])
        ->getBasket()
        ->getBasketItems();

    $userOrderItems = &$orderItemsList[$order['PERSON_TYPE_ID']];

    /** @var \Bitrix\Sale\BasketItem $item */
    foreach ($orderBasketItems as $item)
    {
        $userOrderItems[] = $item->getProductId();
    }

    $userOrderItems = array_unique($userOrderItems);
}

// �������� ������, ���������� �������������� �� ��������� 30 ����
// �� ������� ��������� � ������� ������
$mailList = [];
$mailUsers = [];
$delayedIterator = Bitrix\Sale\Basket::getList([
    'filter' => [
        'DELAY'         => 'Y',
        '>=DATE_INSERT' => $date->add('1 month - 30 days') // �������� 30 ���� (��������� ����� ������� �����)
    ],
    'select' => ['FUSER_ID', 'PRODUCT_ID', 'NAME', 'QUANTITY', 'PRICE', 'CURRENCY'],
]);
while ($delayed = $delayedIterator->fetch())
{
    if (!in_array($delayed['PRODUCT_ID'], $orderItemsList[$delayed['FUSER_ID']]))
    {
        $mailUsers[] = $delayed['FUSER_ID'];
        $mailList[$delayed['FUSER_ID']][] = $delayed;
    }
}

// �������� ���������� � �������������
$usersList = [];
$usersIterator = \Bitrix\Main\UserTable::getList([
    'filter' => ['ID' => $mailUsers],
    'select' => ['ID', 'FULL_NAME', 'EMAIL'],
    'runtime' => [
        new Bitrix\Main\Entity\ExpressionField('FULL_NAME', 'CONCAT(NAME, " ", LAST_NAME)')
    ]
]);
while ($user = $usersIterator->fetch())
{
    $usersList[$user['ID']] = $user;
}

// ���������� ��������
if ($mailList)
{
    foreach ($mailList as $userId => $itemList)
    {
        // ��������� HTML-������ ������� ��� ������
        $delayedString = '<ul>';
        foreach ($mailList[$userId] as $item)
        {
            $delayedString .= '
                <li>' .
                    $item['NAME'] . ' | ' .
                    (int)$item['QUANTITY'] . ' | ' .
                    CCurrencyLang::CurrencyFormat($item['PRICE'], $item['CURRENCY']) .
                '</li>';
        }
        $delayedString .= '</ul>';

        // ���������� ������ � ������� ��������� ������� "USER_DELAYED"
        // ������ ������� ���� �������������� ������� � ������� �����
        $result = Bitrix\Main\Mail\Event::send([
            'EVENT_NAME' => 'USER_DELAYED',
            'LID'        => 's1',
            'C_FIELDS'   => [
                'EMAIL_FROM'   => Bitrix\Main\Config\Option::get('main', 'email_from'),
                'EMAIL_TO'     => $usersList[$userId]['EMAIL'],
                'FULL_NAME'    => $usersList[$userId]['FULL_NAME'],
                'DELAYED_LIST' => $delayedString
            ]
        ]);

        // ����� � ���, ���� ��������� ������ �� �������
        if (!$result->isSuccess())
        {
            Bitrix\Main\Diag\Debug::writeToFile($result->getErrors());
        }
    }
}