<?php

use Bitrix\Main\Mail\Event;

/* 1 задача */
AddEventHandler("iblock", "OnBeforeIBlockElementDelete", array("MyClass", "OnBeforeIBlockElementDeleteHandler"));
/* 2 задача */
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("MyClass", "OnBeforeIBlockElementUpdateHandler"));

define("IBLOCK_CATALOG", 4);

class MyClass
{
    function OnBeforeIBlockElementUpdateHandler(&$arFields)
    {
        if($arFields["IBLOCK_ID"]<=IBLOCK_CATALOG)
        {
            if(isset($arFields['ACTIVE_FROM'])) {
                $today = date("d.m.Y");

                $origin = new DateTimeImmutable($arFields['ACTIVE_FROM']);
                $target = new DateTimeImmutable($today);
                $interval = $origin->diff($target);
                $day =  $interval->format('%a');

                if ($day < 7) {
                    global $APPLICATION;
                    $APPLICATION->throwException("Товар ".$arFields["NAME"]." был создан менее одной недели назад и не может быть изменен.");
                    return false;
                }
            }
        }
    }

    function OnBeforeIBlockElementDeleteHandler(&$arFields)
    {
    	if ($arFields["IBLOCK_ID"] == IBLOCK_CATALOG) {
    		if ($arFields["ACTIVE"] == "N") {
    			$arSelect = array("ID", "IBLOCK_ID", "NAME", "SHOW_COUNTER");
				$arFilter = array("IBLOCK_ID" => IBLOCK_CATALOG, "ID" => $arFields["ID"]);
				$res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
				$arItems = $res->Fetch();
				if ($arItems["SHOW_COUNTER"] > 10000) {
					global $USER;
                    global $APPLICATION;
                    Event::send([
                        "EVENT_NAME" => "USER_COMPANY_MODERATION",
                        "LID" => "s1",
                        "C_FIELDS" => [
                            "USER_ID" => $USER->GetID(),
                            "USER_LOGIN" => $USER->GetLogin(),
                            "SHOW_COUNTER" => $arItems["SHOW_COUNTER"],
                            "NAME_EL" => $arItems["NAME"],
                        ],
                    ]);

                    /* требуется создать событие в административной части и шаблон */
			        $APPLICATION->throwException("Нельзя удалить данный товар, так как он очень популярный на сайте");
			        return false;
				}
    		}
    	}
    }
}

/*

3 задача
1) стоит придерживаться стандартных названий переменных $arSelect и $arFilter
2) в $select не указана данные для выборки
3) getnext используется в getnextelements
4) в $filter добавить параметр активности для уменьшения времени запроса
5)переменная $fields не используется
6) для получения данных лучше воспользоваться конструкцией
    while($obElement = $db_elemens->GetNextElement())
    {
       $el = $obElement->GetFields();
       $el["PROPERTIES"] = $obElement->GetProperties();
            $arResult["ITEMS"][] = $el;
    }
    и получить данные отдельно не в цикле
7) цикл выдачи данных мне не очень нравится

*/
