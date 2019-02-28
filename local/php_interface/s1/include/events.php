<?
// ���������� ������� ������� 1�
// �������� �� ����������� ���� ���������� ������� �������,
// �.�. ������ ������ �������� ������ ��� ����������� �����������
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();
$eventManager->addEventHandler('sale', 'OnSaleBasketBeforeSaved', 'SetActionPrices');

function SetActionPrices(Event $event)
{
    /** @var \Bitrix\Sale\Basket $basket */
    $basket = $event->getParameter('ENTITY');
    $basketItems = $basket->getBasketItems();

    $count = 0;
    /** @var \Bitrix\Sale\BasketItem $item */
    foreach ($basketItems as $item)
    {
        $count++;

        if ($count >= 3)
        {
            $item->setField('PRICE', '1');
            $item->save();
        }
    }
}