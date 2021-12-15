<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/pdfevolution.php
 * 	\ingroup	pdfevolution
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/pdfevolution.lib.php';

// Translations
$langs->load("pdfevolution@pdfevolution");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value','alpha');
$label = GETPOST('label','alpha');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

/*
 * View
 */
$page_name = "pdfevolutionSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = pdfevolutionAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104085Name"),
    -1,
    "pdfevolution@pdfevolution"
);
dol_fiche_end(-1);
// Setup page goes here
$form=new Form($db);
$var=false;



print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';



// MAIN_PDF_DASH_BETWEEN_LINES set to 0 to desable dash line separator


print '<table class="noborder" width="100%">';

_print_title('SetupOptions');

_print_on_off('MAIN_GENERATE_EXCEL_FILE_FOR_DOCUMENT');

_print_title('SetupModelPDFOptions');
_print_on_off('PDFEVOLUTION_DISABLE_COL_HEAD_TITLE');
_print_on_off('MAIN_SHOW_AMOUNT_DISCOUNT');
_print_on_off('MAIN_SHOW_AMOUNT_BEFORE_DISCOUNT');


// Example with imput
//_print_input_form_part('CONSTNAME', 'ParamLabel');

// Example with color
//_print_input_form_part('CONSTNAME', 'ParamLabel', 'ParamDesc', array('type'=>'color'),'input','ParamHelp');

// Example with placeholder
//_print_input_form_part('CONSTNAME','ParamLabel','ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

// Example with textarea
//_print_input_form_part('CONSTNAME','ParamLabel','ParamDesc',array(),'textarea');

print '</table>';

_updateBtn();
print ('<br/><br/>');

$Tcol = array();

$Tcol[] = 'REF';

// commande fourn
$Tcol[] = 'REF_FOURN';

if (! empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE) )
{
    $Tcol[] = 'PHOTO';
}

if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN))
{
    $Tcol[] = 'VAT';
}

$Tcol[] = 'SUBPRICE';
$Tcol[] = 'DISCOUNT';
$Tcol[] = 'UNIT_PRICE_AFTER_DISCOUNT';

if($conf->global->PRODUCT_USE_UNITS){
    $Tcol[] = 'UNIT';
}

$Tcol[] = 'PROGRESS';
$Tcol[] = 'QTY';

if(!empty($conf->expedition->enabled)){
	$Tcol[] = 'REF_EXPEDITION';
}

$Tcol[] = 'TOTALEXCLTAX';
$Tcol[] = 'TOTALINCLTAX';


print '<table class="noborder" width="100%">';


print '<thead>';
print '<tr>';
print '<th class="left"  >'.$langs->trans('Parameters').'</th>';
foreach ($Tcol as $col){
    print '<th class="center"  >'.$langs->trans('PDFEVOLUTION_'.$col).'</th>';
}
print '</tr>';
print '</thead>';

print '<tbody>';

print '<tr class="oddeven" >';
print '<td  >'.$langs->trans('EnableCol').'</td>';
foreach ($Tcol as $col){
    $revertonoff = 1;
    $constUsed = 'PDFEVOLUTION_DISABLE_COL_'.$col;

    if('UNIT_PRICE_AFTER_DISCOUNT' === $col){
        $revertonoff = 0;
        $constUsed = 'PDFEVOLUTION_ADD_UNIT_PRICE_AFTER_DISCOUNT';
    }

    if('REF_FOURN' === $col || 'REF' === $col || 'REF_EXPEDITION' === $col){
        $revertonoff = 0;
        $constUsed = 'PDFEVOLUTION_ADD_COL_'.$col;
    }

    print '<td class="center" >'.ajax_constantonoff($constUsed, array(), null, $revertonoff).'</td>';
}
print '</tr>';


print '<tr class="oddeven" >';
print '<td  >'.$langs->trans('DisplaySeparator').'</td>';
foreach ($Tcol as $col){
    $revertonoff = 1;
    $constUsed = 'PDFEVOLUTION_DISABLE_LEFT_SEP_'.$col;
    print '<td class="center" >'.ajax_constantonoff($constUsed, array(), null, $revertonoff).'</td>';
}
print '</tr>';




print '</tbody>';

//_print_input_form_part($confkey, $title = false, $desc ='', $metas = array(), $type='input', $help = false, $printTableRow = true)




print '</table>';



_updateBtn();

print '</form>';


llxFooter();

$db->close();



function _updateBtn(){
    global $langs;
    print '<div style="text-align: right;" >';
    print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'">';
    print '</div>';
}



/**
 * yes / no select
 * @param string $confkey
 * @param string $title
 * @param string $desc
 * @param $ajaxConstantOnOffInput will be send to ajax_constantonoff() input param
 *
 * exemple _print_on_off('CONSTNAME', 'ParamLabel' , 'ParamDesc');
 */
function _print_on_off($confkey, $title = false, $desc ='', $help = false, $width = 300, $forcereload = false, $ajaxConstantOnOffInput = array())
{
	global $var, $bc, $langs, $conf, $form;
	$var=!$var;

	print '<tr '.$bc[$var].'>';
	print '<td>';


	if(empty($help) && !empty($langs->tab_translate[$confkey . '_HELP'])){
		$help = $confkey . '_HELP';
	}

	if(!empty($help)){
		print $form->textwithtooltip( ($title?$title:$langs->trans($confkey)) , $langs->trans($help),2,1,img_help(1,''));
	}
	else {
		print $title?$title:$langs->trans($confkey);
	}

	if(!empty($desc))
	{
		print '<br><small>'.$langs->trans($desc).'</small>';
	}
	print '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="'.$width.'">';

	if($forcereload){
		$link = $_SERVER['PHP_SELF'].'?action=set_'.$confkey.'&token='.$_SESSION['newtoken'].'&'.$confkey.'='.intval((empty($conf->global->{$confkey})));
		$toggleClass = empty($conf->global->{$confkey})?'fa-toggle-off':'fa-toggle-on font-status4';
		print '<a href="'.$link.'" ><span class="fas '.$toggleClass.' marginleftonly" style=" color: #999;"></span></a>';
	}
	else{
		print ajax_constantonoff($confkey, $ajaxConstantOnOffInput);
	}
	print '</td></tr>';
}

/**
 * Display title
 * @param string $title
 */
function _print_title($title="")
{
	global $langs;
	print '<tr class="liste_titre">';
	print '<th colspan="3">'.$langs->trans($title).'</th>'."\n";
	print '</tr>';
}


function _print_input_form_part($confkey, $title = false, $desc ='', $metas = array(), $type='input', $help = false, $printTableRow = true)
{
    global $var, $bc, $langs, $conf, $db, $inputCount;
    $var=!$var;
    $inputCount = empty($inputCount)?1:($inputCount+1);
    $form=new Form($db);

    $defaultMetas = array(
        'name' => 'value'.$inputCount
    );

    if($type!='textarea'){
        $defaultMetas['type']   = 'text';
        $defaultMetas['value']  = $conf->global->{$confkey};
    }


    $metas = array_merge ($defaultMetas, $metas);
    $metascompil = '';
    foreach ($metas as $key => $values)
    {
        $metascompil .= ' '.$key.'="'.$values.'" ';
    }


    if($printTableRow)
    {
        print '<tr '.$bc[$var].'>';
        print '<td>';

        if(!empty($help)){
            print $form->textwithtooltip( ($title?$title:$langs->trans($confkey)) , $langs->trans($help),2,1,img_help(1,''));
        }
        else {
            print $title?$title:$langs->trans($confkey);
        }

        if(!empty($desc))
        {
            print '<br><small>'.$langs->trans($desc).'</small>';
        }

        print '</td>';
        print '<td align="center" width="20">&nbsp;</td>';
        print '<td align="right" width="300">';
    }

    print '<input type="hidden" name="param'.$inputCount.'" value="'.$confkey.'">';

    print '<input type="hidden" name="action" value="setModuleOptions">';
    if($type=='textarea'){
        print '<textarea '.$metascompil.'  >'.dol_htmlentities($conf->global->{$confkey}).'</textarea>';
    }
    else {
        print '<input '.$metascompil.'  />';
    }

    if($printTableRow){
        print '</td></tr>';
    }
}
