#!/usr/bin/php
<?
// Задание 2а
//
// По моему мнению для решения данной задачи лучше подходит периодический агент на cron,
// но раз в задании сказано оформить как скрипт для запуска из консоли, то делаем таким образом

// Для запуска из консоли нужно явно определить DOCUMENT_ROOT
// Я решил использовать относительный вариант определения, но можно прописать напрямую
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../../../../..');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

@set_time_limit(0); // Убираем ограничения по времени, т.к. процесс может быть длительным

use Bitrix\Sale\Order;

// Внимание! $_SERVER['DOCUMENT_ROOT'] нужен для корректной загрузки модулей Loader-ом
if (!Bitrix\Main\Loader::includeModule('sale'))
{
    return;
}

$date = new Bitrix\Main\Type\DateTime(); // Текущая дата

// Получаем товары, купленные пользователями за последний месяц
$orderItemsList = [];
$orderIterator  = Order::getList([
    'filter' => [
        'CANCELED'      => 'N',
        '>=DATE_INSERT' => $date->add('- 1 month') // Отнимаем 1 месяц
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

// Получаем товары, отложенные пользователями за последние 30 дней
// за вычетом купленных в течение месяца
$mailList = [];
$mailUsers = [];
$delayedIterator = Bitrix\Sale\Basket::getList([
    'filter' => [
        'DELAY'         => 'Y',
        '>=DATE_INSERT' => $date->add('1 month - 30 days') // Отнимаем 30 дней (учитываем ранее отнятый месяц)
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

// Получаем информацию о пользователях
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

// Инициируем рассылку
if ($mailList)
{
    foreach ($mailList as $userId => $itemList)
    {
        // Формируем HTML-список товаров для письма
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

        // Отправляем письмо с помощью почтового события "USER_DELAYED"
        // Данное событие было предварительно создано в админке сайта
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

        // Пишем в лог, если отправить письмо не удалось
        if (!$result->isSuccess())
        {
            Bitrix\Main\Diag\Debug::writeToFile($result->getErrors());
        }
    }
}