<?php

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

class ExcelDoc {


	/**
	 * Overloading the printPDFline function
	 *
	 * @param CommonObject $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param $pdfObject
	 * @param Translate $langs
	 * @param $file
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 * @throws PHPExcel_Exception
	 * @throws PHPExcel_Writer_Exception
	 */
	public function generateExcelFile($object, &$pdfObject, $langs, $file){
		global $conf, $user;

		$langs->load('exceldoc@pdfevolution');

		/**
		 * GENERATION DU FICHIER EXCEL
		 */
		if($object->element == 'propal'
			|| $object->element == 'commande'
			|| $object->element == 'order_supplier'
			|| $object->element == 'supplier_proposal'
			|| $object->element == 'facture'
		)
		{

			$time = time() - 30;

			$path_parts = pathinfo($file);
			$outputFile = $path_parts['dirname']. '/'. $path_parts['filename'].'.xlsx';


			include_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpexcel/Classes/PHPExcel.php';
			include_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpexcel/Classes/PHPExcel/Calculation.php';
			include_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpexcel/Classes/PHPExcel/Cell.php';

			dol_include_once('subtotal/class/subtotal.class.php');

			if(!empty($object->lines))
			{
				$styleTitleArray = array(
					'font' => array(
						'bold' => true,
						'size' => 14
					)
				);
				$styleTitleRowHeight = 20;

				$phpExcel = new PHPExcel();
				$wizard = new PHPExcel_Helper_HTML;

				$documentName = '';
				$documentTitle = '';
				if($object->element == 'propal')
				{
					$documentName = $langs->transnoentities('Propal');
					$documentTitle = $langs->transnoentities('PdfCommercialProposalTitle');
				}elseif($object->element == 'commande'){
					$documentName = $langs->transnoentities('Order');
					$documentTitle = $langs->transnoentities('PdfOrderTitle');
				}
				elseif($object->element == 'order_supplier'){
					$documentName = $langs->transnoentities('SupplierOrder');
					$documentTitle = $langs->transnoentities('SupplierOrder');
				}
				elseif($object->element == 'supplier_proposal'){
					$documentName = $langs->transnoentities('CommercialAsk');
					$documentTitle = $langs->transnoentities('CommercialAsk');
				}
				elseif($object->element == 'facture'){
					$documentName = $langs->transnoentities('Invoice');
					$documentTitle = $langs->transnoentities('Invoice');
				}

				$phpExcel->getProperties()->setCreator($pdfObject->emetteur->name)
					->setLastModifiedBy($user->name)
					->setTitle($pdfObject->emetteur->name.' '.$documentName.' '.$object->ref)
					->setSubject("Office 2007 XLSX ".$pdfObject->emetteur->name.' '.$documentName)
					->setDescription("Dolibarr Documment auto generated")
					->setKeywords($pdfObject->emetteur->name.' '.$documentName.' '.$object->ref)
					->setCategory($documentName);



				$sheet = $phpExcel->getActiveSheet();

				$l=1;

				// Logo
				$logo=$conf->mycompany->dir_output.'/logos/'.$pdfObject->emetteur->logo;
				$addLogo = false;
				if ($pdfObject->emetteur->logo)
				{
					if (is_readable($logo))
					{
						$height=pdf_getHeightForLogo($logo);
						// ADD logo
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setName($pdfObject->emetteur->name);
						$objDrawing->setDescription($pdfObject->emetteur->name);

						$objDrawing->setPath($logo);
						$objDrawing->setCoordinates('A1');
						//setOffsetX works properly
						$objDrawing->setOffsetX(5);
						$objDrawing->setOffsetY(5);
						//set width, height
						//$objDrawing->setWidth(300);
						$objDrawing->setHeight(40);
						$objDrawing->setWorksheet($sheet);
						$addLogo = true;
					}
				}

				if(!$addLogo){
					$sheet->setCellValue('A'.$l, $pdfObject->emetteur->name);
				}

				$sheet->getStyle('A'.$l)->applyFromArray($styleTitleArray);
				$sheet->getRowDimension($l)->setRowHeight(40);

				// add values
				$sheet->setCellValue('B'.$l, $documentTitle);
				// apply style
				$sheet->getStyle('B'.$l)->applyFromArray($styleTitleArray);

				$l++;
				// add values
				$sheet->setCellValue('A'.$l, $langs->transnoentities('Ref'));
				$sheet->setCellValue('B'.$l, $object->ref);
				$sheet->setCellValue('C'.$l, $langs->transnoentities('Date'));
				$sheet->setCellValue('D'.$l, dol_print_date($object->date,"day",false,$langs,true) );
				// apply style
				$sheet->getStyle('A'.$l)->applyFromArray($styleTitleArray);
				$sheet->getStyle('B'.$l)->applyFromArray($styleTitleArray);
				$sheet->getRowDimension($l)->setRowHeight($styleTitleRowHeight);


				$l++;
				// add values RefCustomer
				if($object->element == 'order_supplier' || $object->element == 'supplier_proposal'){
					$sheet->setCellValue('A'.$l, $langs->transnoentities('RefSupplier'));
					$sheet->setCellValue('B'.$l, $object->ref_supplier);
				}
				else
				{
					$sheet->setCellValue('A'.$l, $langs->transnoentities('RefCustomer'));
					$sheet->setCellValue('B'.$l, $object->ref_client);
				}

				if($object->element == 'propal')
				{
					$sheet->setCellValue('C'.$l, $langs->transnoentities('DateEndPropal'));
					$sheet->setCellValue('D'.$l, dol_print_date($object->fin_validite,"day",false,$langs,true) );
				}

				$l++;
				if($object->element == 'order_supplier' || $object->element == 'supplier_proposal'){
					if ($object->thirdparty->code_fournisseur){
						$sheet->setCellValue('A'.$l, $langs->transnoentities('SupplierCode'));
						$sheet->setCellValue('B'.$l, $object->thirdparty->code_fournisseur);

						// apply style
						$sheet->getStyle('A'.$l)->applyFromArray($styleTitleArray);
						$sheet->getStyle('B'.$l)->applyFromArray($styleTitleArray);
						$sheet->getRowDimension($l)->setRowHeight($styleTitleRowHeight);
					}
				}
				elseif ($object->thirdparty->code_client)
				{
					$sheet->setCellValue('A'.$l, $langs->transnoentities('CustomerCode'));
					$sheet->setCellValue('B'.$l, $object->thirdparty->code_client);

					// apply style
					$sheet->getStyle('A'.$l)->applyFromArray($styleTitleArray);
					$sheet->getStyle('B'.$l)->applyFromArray($styleTitleArray);
					$sheet->getRowDimension($l)->setRowHeight($styleTitleRowHeight);
				}


				$l++;
				// add values
				$sheet->setCellValue('A'.$l, $langs->transnoentities('Notes'));
				$sheet->setCellValue('B'.$l, $wizard->toRichTextObject($object->note_public));



				/******************
				# EMETTEUR
				 ******************/
				$l++;
				// add values
				$sheet->setCellValue('A'.$l, $langs->transnoentities('BillFrom'));

				if(!empty($pdfObject->emetteur)){

					$carac_emetteur_name= pdfBuildThirdpartyName($pdfObject->emetteur, $langs);

					$address = $this->object_build_address($langs,$pdfObject->emetteur,$object->thirdparty, '', 0, 'source', $object);

					$sheet->setCellValue('B'.$l, $carac_emetteur_name);

					$l++;
					$sheet->setCellValue('B'.$l, $address->address);

					$sheet->setCellValue('C'.$l, $address->zip);
					// Apply text format to zip code
					$sheet->getStyle('C'.$l)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );

					$sheet->setCellValue('D'.$l, $address->town);
					$sheet->setCellValue('E'.$l, $address->country);

					$l++;
					$sheet->setCellValue('B'.$l, $address->phone);
					$sheet->setCellValue('C'.$l, $address->email);
					$sheet->setCellValue('D'.$l, $address->VATIntra);

				}
				else{
					$l = $l +2;
				}


				/******************
				# BILL TO
				 ******************/
				$l++;
				$l++;
				// add values
				$sheet->setCellValue('A'.$l, $langs->transnoentities('BillTo'));

				if(!empty($object->thirdparty)){


					// If CUSTOMER contact defined, we use it
					$usecontact=false;
					$arrayidcontact=$object->getIdContact('external','CUSTOMER');
					if (count($arrayidcontact) > 0)
					{
						$usecontact=true;
						$result=$object->fetch_contact($arrayidcontact[0]);
					}

					//Recipient name
					// On peut utiliser le nom de la societe du contact
					if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
						$thirdparty = $object->contact;
					} else {
						$thirdparty = $object->thirdparty;
					}

					$carac_client_name= pdfBuildThirdpartyName($thirdparty, $langs);

					$address = $this->object_build_address($langs,$this->emetteur,$object->thirdparty,($usecontact?$object->contact:''),$usecontact,'target',$object);

					$sheet->setCellValue('B'.$l, $carac_client_name);

					$l++;
					$sheet->setCellValue('B'.$l, $address->address);

					$sheet->setCellValue('C'.$l, $address->zip);
					// Apply text format to zip code
					$sheet->getStyle('C'.$l)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );

					$sheet->setCellValue('D'.$l, $address->town);
					$sheet->setCellValue('E'.$l, $address->country);

					$l++;
					$sheet->setCellValue('B'.$l, $address->phone);
					$sheet->setCellValue('C'.$l, $address->email);
					$sheet->setCellValue('D'.$l, $address->VATIntra);

				}
				else{
					$l = $l +2;
				}

				$l = 18; // start line number for object lines


				$sheet->setCellValue('A'.($l-1), $langs->transnoentities('excelDisclamer'));

				$colKeys = array(
					'ref',
					'desc',
					'type',
					'subprice',
					'reduction',
					'subpriceWithReduction',
					'qty',
					'totalHT',
					'tva',
					'totalTVA',
					'totalTTC',
					'LineUniqueId',
				);



				if($object->element == 'facture'){
					if($object->type == Facture::TYPE_SITUATION){
						$colKeys[] = 'prev_progress';
						$colKeys[] = 'new_progress';
						$colKeys[] = 'total_progress';
					}
				}
				$cols = array();
				$alphabet = range('A', 'Z');
				foreach ($colKeys as $k => $colKey){
					$cols[$colKey] = $alphabet[$k];
				}

				$foramatTotaux = '# ###,00;[RED]-# ###,00';

				$styleArray = array(
					'font' => array(
						'bold' => true
					)
				);

				foreach ($cols as $colTitle => $col)
				{
					$sheet->setCellValue($col.$l, $langs->transnoentities('excelCol_'.$colTitle));
					$sheet->getStyle($col.$l)->applyFromArray($styleArray);
					$sheet->getStyle($col.$l)
						->getFill()
						->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
						->getStartColor()
						->setRGB('dbc9c0');
				}

				// Col dimentions
				$sheet->getColumnDimensionByColumn(0)->setAutoSize(false);
				$sheet->getColumnDimensionByColumn(0)->setWidth(30);
				$sheet->getColumnDimensionByColumn(1)->setAutoSize(false);
				$sheet->getColumnDimensionByColumn(1)->setWidth(70);

				//$sheet->freezePane('A'.($l+1));

				$linesContentStart = $l + 1;
				foreach ($object->lines as $i => $line)
				{
					/** @var FactureLigne $line */
					$l ++;
					$sheet->getRowDimension($l)->setRowHeight(100);

					$sheet->setCellValue($cols['LineUniqueId'].$l, $line->id);
					if((int)$line->product_type === 0 || (int)$line->product_type === 1)
					{
						if((int)$line->product_type === 1){
							$product_type = 'service';
						}
						else{
							$product_type = 'produit';
						}

						$sheet->setCellValue($cols['type'].$l, $product_type);

						if($object->element == 'order_supplier' || $object->element == 'supplier_proposal') {
							$sheet->setCellValue($cols['ref'].$l, $line->ref_fourn);
						}
						else{
							$sheet->setCellValue($cols['ref'].$l, $line->ref);
						}


						$desc = $line->libelle.' '.$line->desc;
						$richText = $wizard->toRichTextObject('<?xml encoding="UTF-8">' . $desc);

						$sheet->setCellValue($cols['desc'].$l,  $richText);
						$sheet->getStyle($cols['desc'].$l)->getAlignment()->setWrapText(true);



						$sheet->setCellValue($cols['subprice'].$l, $line->subprice);
						$sheet->setCellValue($cols['reduction'].$l, $line->remise_percent);


						$subpricewithreduction = '';
						if($line->qty>0){
							$subpricewithreduction = round($line->total_ht / $line->qty, 4);
						}
						$sheet->setCellValue($cols['subpriceWithReduction'].$l, $subpricewithreduction);

						$sheet->setCellValue($cols['qty'].$l, $line->qty);
						$sheet->setCellValue($cols['tva'].$l, $line->tva_tx);
						if($object->element == 'facture') {
							if ($object->type == Facture::TYPE_SITUATION) {
								$prev_progress = 0;
								if (method_exists($object->lines[$i], 'get_prev_progress')){
									$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
								}

								$sheet->setCellValue($cols['prev_progress'].$l, $prev_progress);
								$sheet->setCellValue($cols['new_progress'].$l, $object->lines[$i]->situation_percent - $prev_progress);
								$sheet->setCellValue($cols['total_progress'].$l, pdf_getlineprogress($object, $i, $langs));
							}
						}
						$totalExclTax = doubleval(price2num(pdf_getlinetotalexcltax($object, $i, $langs)));
						$totalWithTax = doubleval(price2num(pdf_getlinetotalwithtax($object, $i, $langs)));

						$sheet->setCellValue($cols['totalHT'].$l, $totalExclTax);
						$sheet->setCellValue($cols['totalTVA'].$l, $totalWithTax - $totalExclTax);
						$sheet->setCellValue($cols['totalTTC'].$l, $totalWithTax);


						$sheet->getStyle($cols['totalHT'].$l.':'.$cols['totalTTC'].$l)->getNumberFormat()->setFormatCode($foramatTotaux);
					}
					else{
						$product_type = 'info';
						$desc = $line->libelle.' '.$line->desc;
						$sheet->setCellValue($cols['desc'].$l,  $wizard->toRichTextObject('<?xml encoding="UTF-8">' . $desc));

						if(class_exists('TSubtotal')){
							if(TSubtotal::isModSubtotalLine($line))
							{
								//$sheet->mergeCells('B'.$l.':G'.$l);

								$styleArray = array(
									'font' => array(
										'bold' => true
									)
								);
								$sheet->getStyle('B'.$l)->applyFromArray($styleArray);


								if(TSubtotal::isTitle($line))
								{
									$product_type = 'title';
									$desc = TSubtotal::getTitleLabel($line);
									$sheet->setCellValue($cols['desc'].$l, $wizard->toRichTextObject('<?xml encoding="UTF-8">' . $desc));
								}
								elseif(TSubtotal::isSubtotal($line)){
									$product_type = 'subtotal';

									$sheet->setCellValue($cols['ref'].$l, 'Total : '.TSubtotal::getTitleLabel($line));
									$sheet->getStyle($cols['ref'].$l)
										->getAlignment()
										->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

									$TInfo = $this->getTotalLineFromObject($object, $line);
									$TTotal_tva = $TInfo[3];
									$TTotal_ht = $TInfo[0];
									$TTotal_ttc = $TInfo[2];


									//$sheet->setCellValue($cols['totalHT'].$l, $TTotal_ht);
									//$sheet->setCellValue($cols['totalTVA'].$l, $TTotal_tva);
									$sheet->setCellValue($cols['totalTTC'].$l, $TTotal_ttc);


								}
								elseif(TSubtotal::isFreeText($line)){
									$product_type = 'freetext';
									$desc = TSubtotal::getTitleLabel($line);
									$sheet->setCellValue($cols['desc'].$l, $wizard->toRichTextObject($desc));
								}
							}
						}

						$sheet->setCellValue($cols['type'].$l, $product_type);

					}

					$sheet->getRowDimension($l)->setRowHeight(-1);
				}
				$linesContentEnd = $l;
				$endCol = max ($cols);
				$startCol = min ($cols);


				$sheet->getStyle($startCol.$linesContentStart.':'.$endCol.$l)->getAlignment()->applyFromArray(
					array('vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP)
				);

				$sheet->getStyle($startCol.$l.':'.$endCol.$l)->getNumberFormat()->setFormatCode($foramatTotaux);

				$sheet->getStyle($startCol.($linesContentStart-1).':'.$endCol.($l+1))->applyFromArray(
					array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN,
								'color' => array('rgb' => 'a5a5a5')
							)
						)
					)
				);

				// Affichage des totaux
				$l++;



				// Application du style des totaux finaux
				$sheet->getStyle($startCol.$l.':'.$endCol.$l)->applyFromArray($styleArray);
				$sheet->getStyle($startCol.$l.':'.$endCol.$l)->getNumberFormat()->setFormatCode($foramatTotaux);
				$sheet->getStyle($startCol.$l.':'.$endCol.$l)->getFill()
					->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
					->getStartColor()
					->setRGB('fafafa');



				$sheet->setCellValue($cols['ref'].$l, $langs->transnoentities('Total'));
				$sheet->setCellValue($cols['totalHT'].$l, '=SUM('.$cols['totalHT'].$linesContentStart.':'.$cols['totalHT'].$linesContentEnd.')',1);
				$sheet->setCellValue($cols['totalTVA'].$l, '=SUM('.$cols['totalTVA'].$linesContentStart.':'.$cols['totalTVA'].$linesContentEnd.')',1);
				$sheet->setCellValue($cols['totalTTC'].$l, '=SUM('.$cols['totalTTC'].$linesContentStart.':'.$cols['totalTTC'].$linesContentEnd.')',1);



				// Sauvegarde du fichier au format excel 2007
				$writer = new PHPExcel_Writer_Excel2007($phpExcel);
				$writer->setPreCalculateFormulas(true);
				$writer->save($outputFile);
				touch($outputFile, $time);
			}
		}
	}


	/**
	 *   	Return a string with full address formated
	 *
	 * 		@param	Translate	$outputlangs		Output langs object
	 *   	@param  Societe		$sourcecompany		Source company object
	 *   	@param  Societe		$targetcompany		Target company object
	 *      @param  Contact		$targetcontact		Target contact object
	 * 		@param	int			$usecontact			Use contact instead of company
	 * 		@param	int			$mode				Address type ('source', 'target', 'targetwithdetails')
	 * 		@return	object							$address
	 */
	function object_build_address($outputlangs,$sourcecompany,$targetcompany='',$targetcontact='',$usecontact=0,$mode='source')
	{
		global $conf;
		$stringaddress = '';
		$address = new stdClass();
		$address->socName = '';
		$address->contactName = '';
		$address->address = '';
		$address->state = '';
		$address->zip = '';
		$address->town = '';
		$address->country = '';
		$address->phone = '';
		$address->fax = '';
		$address->email = '';
		$address->url = '';
		$address->VATIntra = '';
		$address->ProfId1 = '';
		$address->ProfId2 = '';
		$address->ProfId3 = '';
		$address->ProfId4 = '';


		if ($mode == 'source' && ! is_object($sourcecompany)) return -1;
		if ($mode == 'target' && ! is_object($targetcompany)) return -1;

		if (! empty($sourcecompany->state_id) && empty($sourcecompany->departement)) $sourcecompany->departement=getState($sourcecompany->state_id); //TODO: Deprecated
		if (! empty($sourcecompany->state_id) && empty($sourcecompany->state)) $sourcecompany->state=getState($sourcecompany->state_id);
		if (! empty($targetcompany->state_id) && empty($targetcompany->departement)) $targetcompany->departement=getState($targetcompany->state_id);

		if ($mode == 'source')
		{

			if (!empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)){
				$address->country = $outputlangs->transnoentitiesnoconv("Country".$sourcecompany->country_code);
			}

			$address->address = $sourcecompany->address;
			$address->town = $sourcecompany->town;
			$address->state = $sourcecompany->state;
			$address->zip = $sourcecompany->zip;



			if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS))
			{
				// Phone
				$address->phone = $sourcecompany->phone;
				// Fax
				$address->fax = $sourcecompany->fax;
				// EMail
				$address->email = $sourcecompany->email;
				// Web
				$address->url = $sourcecompany->url;
			}
		}

		if ($mode == 'target' || $mode == 'targetwithdetails')
		{
			if ($usecontact)
			{



				if (!empty($targetcontact->address)) {
					$address->address   = $targetcontact->address;
					$address->town      = $targetcontact->town;
					$address->state     = $targetcontact->state;
					$address->zip       = $targetcontact->zip;


					if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
						$address->country = $outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code);
					}

				}else {
					$address->address   = $targetcompany->address;
					$address->town      = $targetcompany->town;
					$address->state     = $targetcompany->state;
					$address->zip       = $targetcompany->zip;


					if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) {
						$address->country = $outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code);
					}
				}


				if (! empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS))
				{
					// Phone
					if (! empty($targetcontact->phone_pro)) $address->phone .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
					if (! empty($targetcontact->phone_pro) && ! empty($targetcontact->phone_mobile)) $address->phone .= " / ";
					if (! empty($targetcontact->phone_mobile)) $address->phone .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);

					// Fax
					$address->fax = $targetcontact->fax;
					// EMail
					$address->email = $targetcontact->email;
					// Web
					$address->url = $targetcontact->url;
				}


			}
			else
			{

				$address->address   = $targetcompany->address;
				$address->town      = $targetcompany->town;
				$address->state     = $targetcompany->state;
				$address->zip       = $targetcompany->zip;


				if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) {
					$address->country = $outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code);
				}

				if (! empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS))
				{
					// Phone
					if (! empty($targetcompany->phone_pro)) $address->phone .= $outputlangs->convToOutputCharset($targetcompany->phone_pro);
					if (! empty($targetcompany->phone_pro) && ! empty($targetcompany->phone_mobile)) $address->phone .= " / ";
					if (! empty($targetcompany->phone_mobile)) $address->phone .= $outputlangs->convToOutputCharset($targetcompany->phone_mobile);

					// Fax
					$address->fax = $targetcompany->fax;
					// EMail
					$address->email = $targetcompany->email;
					// Web
					$address->url = $targetcompany->url;
				}




			}

			// Intra VAT
			$address->VATIntra = $targetcompany->tva_intra;


			// Professionnal Ids
			$address->ProfId1 = $targetcompany->idprof1;
			$address->ProfId2 = $targetcompany->idprof2;
			$address->ProfId3 = $targetcompany->idprof3;
			$address->ProfId4 = $targetcompany->idprof4;
		}

		return $address;
	}

	/**
	 *  TODO le calcul est faux dans certains cas,  exemple :
	 *	T1
	 *		|_ l1 => 50 €
	 *		|_ l2 => 40 €
	 *		|_ T2
	 *			|_l3 => 100 €
	 *		|_ ST2
	 *		|_ l4 => 23 €
	 *	|_ ST1
	 *
	 * On obtiens ST2 = 100 ET ST1 = 123 €
	 * Alors qu'on devrais avoir ST2 = 100 ET ST1 = 213 €
	 *
	 * @param	$use_level		isn't used anymore
	 */
	function getTotalLineFromObject(&$object, &$line, $use_level=false, $return_all=0) {
		global $conf;

		$rang = $line->rang;
		$qty_line = $line->qty;

		$total = 0;
		$total_tva = 0;
		$total_ttc = 0;
		$TTotal_tva = array();

		$sign=1;
		if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

		if (GETPOST('action') == 'builddoc') $builddoc = true;
		else $builddoc = false;

		dol_include_once('/subtotal/class/subtotal.class.php');
		foreach($object->lines as $l) {
			//print $l->rang.'>='.$rang.' '.$total.'<br/>';
			if($l->rang>=$rang) {
				//echo 'return!<br>';
				if (!$return_all) return $total;
				else return array($total, $total_tva, $total_ttc, $TTotal_tva);
			}
			else if(TSubtotal::isTitle($l, 100 - $qty_line))
			{
				$total = 0;
				$total_tva = 0;
				$total_ttc = 0;
				$TTotal_tva = array();
			}
			elseif(!TSubtotal::isTitle($l) && !TSubtotal::isSubtotal($l)) {

				// TODO retirer le test avec $builddoc quand Dolibarr affichera le total progression sur la card et pas seulement dans le PDF
				if ($builddoc && $object->element == 'facture' && $object->type==Facture::TYPE_SITUATION)
				{
					if ($l->situation_percent > 0)
					{
						$prev_progress = 0;
						$progress = 1;
						if (method_exists($l, 'get_prev_progress'))
						{
							$prev_progress = $l->get_prev_progress($object->id);
							$progress = ($l->situation_percent - $prev_progress) / 100;
						}

						$result = $sign * ($l->total_ht / ($l->situation_percent / 100)) * $progress;
						$total+= $result;
						// TODO check si les 3 lignes du dessous sont corrects
						$total_tva += $sign * ($l->total_tva / ($l->situation_percent / 100)) * $progress;
						$TTotal_tva[$l->tva_tx] += $sign * ($l->total_tva / ($l->situation_percent / 100)) * $progress;
						$total_ttc += $sign * ($l->total_tva / ($l->total_ttc / 100)) * $progress;
					}
				}
				else
				{
					$total += $l->total_ht;
					$total_tva += $l->total_tva;
					$TTotal_tva[$l->tva_tx] += $l->total_tva;
					$total_ttc += $l->total_ttc;
				}
			}

		}
		if (!$return_all) return $total;
		else return array($total, $total_tva, $total_ttc, $TTotal_tva);
	}
}
