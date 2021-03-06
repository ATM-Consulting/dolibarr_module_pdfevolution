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
 * \file    class/actions_pdfevolution.class.php
 * \ingroup pdfevolution
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionspdfevolution
 */
class Actionspdfevolution
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/*
     * Overloading the defineColumnField function
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   PDF object      $pdfDoc         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	public function defineColumnField($parameters, &$pdfDoc, &$action, $hookmanager)
    {
        global $conf, $user, $langs;


        // Translations
        $langs->loadLangs(array("pdfevolution@pdfevolution"));
        
        $contexts = explode(':',$parameters['context']);

		$def = array(
			'rank' => 100,
			'width' => 25, // in mm
			'status' => false,
			'title' => array(
				'label' => $langs->transnoentities('TotalTTCShort')
			),
			'border-left' => true, // add left line separator
		);

		if (! empty($conf->global->PDFEVOLUTION_ADD_TOTAL_INCLUDING_TAX)){
			$def['status'] = true;
		}

		$pdfDoc->insertNewColumnDef('totalincltax', $def, 'totalexcltax',1);


        $def = array(
            'rank' => 55,
            'width' => 20, // in mm
            'status' => false,
            'title' => array(
                'label' => $langs->transnoentities('UnitPriceAfterDiscount')
            ),
            'border-left' => true, // add left line separator
        );

        if ($pdfDoc->atleastonediscount && !empty($conf->global->PDFEVOLUTION_ADD_UNIT_PRICE_AFTER_DISCOUNT)){
            $def['status'] = true;
        }

        $pdfDoc->insertNewColumnDef('UnitPriceAfterDiscount', $def, 'discount',1);


        $def = array(
            'rank' => 55,
            'width' => 20, // in mm
            'status' => false,
            'title' => array(
                'label' => $langs->transnoentities('RefFourn')
            ),
            'border-left' => true, // add left line separator
        );


        if (!empty($conf->global->PDFEVOLUTION_ADD_COL_REF_FOURN)
            && !empty($parameters['object'])
            && $parameters['object']->element == 'order_supplier'
        ){
            $def['status'] = true;
        }

        $pdfDoc->insertNewColumnDef('SupplierRef', $def, 'desc',1);



        $def = array(
            'rank' => 55,
            'width' => 25, // in mm
            'status' => false,
            'title' => array(
                'label' => $langs->transnoentities('Ref')
            ),
            'border-left' => true, // add left line separator
        );

        if (!empty($conf->global->PDFEVOLUTION_ADD_COL_REF)){
            $def['status'] = true;
        }

        $pdfDoc->insertNewColumnDef('Ref', $def, 'desc',1);


		$def = array(
			'rank' => 55,
			'width' => 30, // in mm
			'status' => false,
			'title' => array(
				'label' => $langs->transnoentities('RefExpedition')
			),
			'border-left' => true, // add left line separator
		);
		if (!empty($parameters['object'])
			&& $parameters['object']->element == 'facture'
			&& !empty($parameters['object']->linkedObjects['shipping'])
			&& !empty($conf->expedition->enabled)
			&& !empty($conf->global->PDFEVOLUTION_ADD_COL_REF_EXPEDITION)){
			$def['status'] = true;
		}
		$pdfDoc->insertNewColumnDef('RefExpedition', $def, 'qty',1);

//		$def = array(
//			'rank' => 55,
//			'width' => 30, // in mm
//			'status' => false,
//			'title' => array(
//				'label' => $langs->transnoentities('Asset')
//			),
//			'border-left' => true, // add left line separator
//		);
//		if (!empty($parameters['object'])
//			&& $parameters['object']->element == 'shipping'
//			&& !empty($conf->expedition->enabled)
//			&& !empty($conf->assetatm->enabled)){
//			$def['status'] = true;
//		}
//		$pdfDoc->insertNewColumnDef('AssetExpedition', $def, 'desc',1);


        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_TOTALEXCLTAX)){
            $pdfDoc->cols['totalexcltax']['status'] = false;
        }

        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_DISCOUNT)){
            $pdfDoc->cols['discount']['status']     = false;
        }

        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_UNIT)){
            $pdfDoc->cols['unit']['status']         = false;
        }

        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_PROGRESS)){
            $pdfDoc->cols['progress']['status']     = false;
        }

        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_QTY)){
            $pdfDoc->cols['qty']['status']          = false;
        }

        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_SUBPRICE)){
            $pdfDoc->cols['subprice']['status']     = false;
        }

        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_VAT)){
            $pdfDoc->cols['vat']['status']          = false;
        }

        if(!empty($conf->global->PDFEVOLUTION_DISABLE_COL_PHOTO)){
            $pdfDoc->cols['photo']['status']        = false;
        }


        $Tcol = array(
            'TOTALEXCLTAX'
            ,'DISCOUNT'
            ,'UNIT_PRICE_AFTER_DISCOUNT'
            ,'UNIT'
            ,'PROGRESS'
            ,'QTY'
            ,'SUBPRICE'
            ,'VAT'
            ,'PHOTO'
            ,'REF'
            ,'SUPPLIER_REF'
			,'REF_EXPEDITION'
        );

        foreach ($Tcol as $col){
            $constUsed = 'PDFEVOLUTION_DISABLE_LEFT_SEP_'.$col;
            if(!empty($conf->global->{$constUsed})){
                $pdfDoc->cols[strtolower($col)]['border-left']        = false;
            }
        }
    }
    
    /*
     * Overloading the printPDFline function
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printPDFline($parameters, &$pdfDoc, &$action, $hookmanager)
    {
        global $conf, $user, $langs;
        $pdf =& $parameters['pdf'];
        $i = $parameters['i'];
        $outputlangs = $parameters['outputlangs'];

        $returnVal = 0;

        $object = $parameters['object'];

		// is subtotal ?
		if(is_callable('TSubtotal::isModSubtotalLine')){
			if(TSubtotal::isModSubtotalLine($object->lines[$i])){
				return 0;
			}
		}


        if ($pdfDoc->getColumnStatus('UnitPriceAfterDiscount'))
        {

            $sign=1;
            if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;
            
            $subprice = ($conf->multicurrency->enabled && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice);
            $subprice = $sign * $subprice;
            
            $celText = '';
            if ($object->lines[$i]->special_code == 3){
                $celText = '';
            }
            elseif(!empty($object->lines[$i]->remise_percent)){
                $subpriceWD = $subprice - ($subprice * $object->lines[$i]->remise_percent / 100) ;
                $celText = price($subpriceWD, 0, $outputlangs);
            }
            
            
            if(!empty($celText)){
                $pdfDoc->printStdColumnContent($pdf, $parameters['curY'], 'UnitPriceAfterDiscount', $celText );
                $parameters['nexY'] = max($pdf->GetY(),$parameters['nexY']);
            }

            $returnVal =  1;
        }


        if ($pdfDoc->getColumnStatus('totalincltax'))
		{



			$sign=1;
			if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

			$price = $sign * $object->lines[$i]->total_ttc;

			$celText = price($price);
			if ($object->lines[$i]->special_code == 3){
				$celText = '';
			}


			if(!empty($celText)){
				$pdfDoc->printStdColumnContent($pdf, $parameters['curY'], 'totalincltax', $celText );
				$parameters['nexY'] = max($pdf->GetY(),$parameters['nexY']);
			}

			$returnVal =  1;
		}

        if ($pdfDoc->getColumnStatus('Ref'))
        {
            $pdfDoc->printStdColumnContent($pdf, $parameters['curY'], 'Ref',  $object->lines[$i]->ref );
            $parameters['nexY'] = max($pdf->GetY(),$parameters['nexY']);

            $returnVal =  1;
        }

        if ($pdfDoc->getColumnStatus('SupplierRef'))
        {
            $pdfDoc->printStdColumnContent($pdf, $parameters['curY'], 'SupplierRef',  $object->lines[$i]->ref_fourn );
            $parameters['nexY'] = max($pdf->GetY(),$parameters['nexY']);

            $returnVal =  1;
        }

        // Shipping
		if ($pdfDoc->getColumnStatus('RefExpedition'))
		{

			$object->fetchObjectLinked();
			$object->lines[$i]->fetchObjectLinked();

			$shipping_content = '';

			if(!empty($object->linkedObjects['shipping'])){
				if(count($object->linkedObjects['shipping']) == 1){
					$shipping =  array_shift($object->linkedObjects['shipping']);
					$shipping_content = $shipping->ref;
					if(!empty($shipping->date_delivery))$shipping_content .=' '.date('d/m/Y', $shipping->date_delivery);
				}else {
					$object->lines[$i]->fetchObjectLinked();
					if (!empty($object->lines[$i]->linkedObjects['shipping']))
					{
						$shipping =  array_shift($object->lines[$i]->linkedObjects['shipping']);
						$shipping_content = $shipping->ref;
						if(!empty($shipping->date_delivery))$shipping_content .=' '.date('d/m/Y', $shipping->date_delivery);
					}
				}
			}

			$pdfDoc->printStdColumnContent($pdf, $parameters['curY'], 'RefExpedition', $shipping_content);
			$parameters['nexY'] = max($pdf->GetY(),$parameters['nexY']);

			$returnVal =  1;
		}

		// Shipping Asset ATM
		if ($pdfDoc->getColumnStatus('AssetExpedition'))
		{
			$asset_content  ='';

			$pdfDoc->printStdColumnContent($pdf, $parameters['curY'], 'AssetExpedition', $asset_content);
			$parameters['nexY'] = max($pdf->GetY(),$parameters['nexY']);

			$returnVal =  1;
		}



        return $returnVal;
    }
}
