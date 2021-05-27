<?php

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

$module_id = 'local.refreshnews';

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight($module_id) < "S") {
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}
Loader::includeModule('iblock');
Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$listIblocksItem = function ($IBLOCK_TYPE = '') {
	$arIblock = IblockTable::getList(array(
		'filter' => array(// 'CODE' => 'news'
		),
	))->fetchAll();
	$arIblocks = [];
	// $arIblocks['empty'] = '--';
	foreach ($arIblock as $arItem) {
		$arIblocks[$arItem["ID"]] = $arItem["NAME"];
	}
	return $arIblocks;
};

//				$handlersmodule = \Bitrix\Main\EventManagerEventManager::getInstance()->findEventHandlers("main", "OnProlog");
//$handlers = GetModuleEvents("main", "OnProlog", true);
$handlersmodule = [];

$connection = Bitrix\Main\Application::getConnection();
$siblingsElementIterator = $connection->query(
	("SELECT  ID,
        TIMESTAMP_X,
        FROM_MODULE_ID,
        MESSAGE_ID,
        TO_MODULE_ID,
        TO_PATH,
        TO_CLASS,
        TO_METHOD,
        TO_METHOD_ARG,
        VERSION
  FROM b_module_to_module WHERE TO_MODULE_ID='$module_id'  ORDER BY TIMESTAMP_X DESC
  LIMIT 100")
);

while ($siblingsElement = $siblingsElementIterator->fetch()) {
	$siblingsElement['TIMESTAMP_X'] = explode(' ', $siblingsElement['TIMESTAMP_X'])[0];
	$handlersmodule[] = $siblingsElement;
}

$aTabs = array(
	array(
		'DIV' => 'OSNOVNOE',
		'TAB' => Loc::getMessage('LOCAL_INFOBLOCK_TAB_OSNOVNOE'),
		'OPTIONS' => array(
			array('infobloki_dlya_skanirovaniya',
				Loc::getMessage('LOCAL_INFOBLOCK_OPTION_INFOBLOKI_DLYA_SKANIROVANIYA_TITLE'),
				'',
				array('multiselectbox',
					$listIblocksItem()
				)
			),
			array('logi_razreshit',
				Loc::getMessage('LOCAL_INFOBLOCK_OPTION_LOGI_RAZRESHIT_TITLE')
			, '', array('checkbox', "Y"), 'Y'
			),
		),
	),
	array(
		'DIV' => 'LOGI',
		'TAB' => Loc::getMessage('LOCAL_INFOBLOCK_TAB_LOGI'),
		'OPTIONS' => array(
			array('poslednyaya_zais', Loc::getMessage('LOCAL_INFOBLOCK_OPTION_POSLEDNYAYA_ZAIS_TITLE'), '', array('textarea', 0, 0)),
		),
	),
	array(
		'DIV' => 'LOGGI',
		'TAB' => Loc::getMessage('LOCAL_INFOBLOCK_OPTION_SSYLKA_NA_CHENDLERY_TITLE'),
		'OPTIONS' => 'LOGGI',
	),

	array(
		"DIV" => "rights",
		"TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
		"TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS"),
		"OPTIONS" => array()
	),
);
#Сохранение

if ($request->isPost() && $request['Apply'] && check_bitrix_sessid()) {

	foreach ($aTabs as $aTab) {
		foreach ($aTab['OPTIONS'] as $arOption) {
			if (!is_array($arOption))
				continue;

			if ($arOption['note'])
				continue;


			$optionName = $arOption[0];

			$optionValue = $request->getPost($optionName);

			if ($optionValue == 'empty') {
				Option::delete($module_id, array(
					"name" => $optionName
				));
			}
			if ($optionValue != 'empty') {
				Option::set($module_id, $optionName, is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
			}
		}
	}
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

?>
<? $tabControl->Begin(); ?>
<form method='post'
      action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($request['mid']) ?>&amp;lang=<?= $request['lang'] ?>'
      name='local_refreshnews_settings'>

	<? foreach ($aTabs as $aTab):
		if ($aTab['OPTIONS']):?>
			<? $tabControl->BeginNextTab(); ?>
			<? if ($aTab['OPTIONS'] && $aTab['OPTIONS'] == 'LOGGI') {

				// -------------
				/*if (count($handlersmodule) > 0): */ ?><!--
                    <table>
                        <thead>
                        <tr>
                            <th><?php /*echo implode('</th><th>', array_keys(current($handlersmodule))); */ ?></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php /*foreach ($handlersmodule as $row): array_map('htmlentities', $row); */ ?>
                            <tr>
                                <td><?php /*echo implode('</td><td>', $row); */ ?></td>
                            </tr>
						<?php /*endforeach; */ ?>
                        </tbody>
                    </table>
				--><?php /*endif;*/

				// -------------

			} ?>
			<? __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>

		<? endif;
	endforeach; ?>

	<?
	$tabControl->BeginNextTab();

	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");

	$tabControl->Buttons(); ?>

    <input type="submit" name="Apply" value="<? echo GetMessage('MAIN_SAVE') ?>">
    <input type="hidden" name="Update" value="Y">
    <input type="reset" name="reset" value="<? echo GetMessage('MAIN_RESET') ?>">
	<?= bitrix_sessid_post(); ?>
</form>
<? $tabControl->End(); ?>

