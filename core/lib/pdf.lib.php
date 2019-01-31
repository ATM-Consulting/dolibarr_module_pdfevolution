<?php 
/**
 *	Return line total amount discount
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string							Return total of line excl tax
 */
if(!function_exists('pdf_getLineTotalDiscountAmount')){
    function pdf_getLineTotalDiscountAmount($object, $i, $outputlangs, $hidedetails=0)
    {
        global $conf, $hookmanager;
        $sign=1;
        if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;
        if ($object->lines[$i]->special_code == 3)
        {
            return $outputlangs->transnoentities("Option");
        }
        else
        {
            
            if (is_object($hookmanager))
            {
                $special_code = $object->lines[$i]->special_code;
                if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
                
                $parameters = array(
                    'i'=>$i,
                    'outputlangs'=>$outputlangs,
                    'hidedetails'=>$hidedetails,
                    'special_code'=>$special_code
                );
                
                $action='';
                
                if( $hookmanager->executeHooks('getlinetotalremise',$parameters,$object,$action)>0)
                {
                    return $hookmanager->resPrint;    // Note that $action and $object may have been modified by some hooks
                }
            }
            
            if (empty($hidedetails) || $hidedetails > 1) return $sign * ( ($object->lines[$i]->subprice * $object->lines[$i]->qty) - $object->lines[$i]->total_ht );
        }
        return '';
    }
}
