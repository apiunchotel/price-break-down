<?php

namespace App\Service;

use App\Model\TaxeDetail;

/**
 * Service Taxes Hi
 *
 * @author Essahel Adil <adil.essahel@uncubus.com> 
 */
class TaxesService
{

    use \App\Traits\SimilarFieldsCopier;

    /**
     * Les taxes a partir de prix HT
     * 
     * @param float $priceHt Price HT (prix sans les taxes inculs et exclus)
     * @param array $taxes Les taxes format standard
     * @param int $nbPerson Nombre des personnes
     * @param int $nbDays  Nombre des jours de reservation
     * @return array
     */
    public function getTaxeFromHt(float $priceHt, array $taxes, int $nbPerson, int $nbDays): array
    {
        $inc = (int) true;
        $exc = (int) false;
        $details = [$inc => 0, $exc => 0];
        foreach ($taxes as $taxe) {
            $detail = $this->convertToObject($taxe, TaxeDetail::class);
            $details[(int) $detail->getTxInc()] = $this->_calculMontantTaxe($priceHt, $nbPerson, $nbDays, $detail);
        }
        $ttc = $priceHt + array_sum($details);
        $ht = $priceHt;
        $sale = $priceHt + $details[$inc];

        return [
            'ttc' => $ttc,
            'ht' => $ht,
            'sale' => $sale,
        ];
    }

    public function convertToObject(array $taxe): TaxeDetail
    {
        return $this->copyTo((object) $taxe, TaxeDetail::class);
    }

    /**
     * 
     * @param float $priceHt
     * @param int $nbPerson
     * @param int $nbDays
     * @param TaxeDetail $taxeDetail
     * @return float
     * @throws \LogicException
     */
    private function _calculMontantTaxe(float $priceHt, int $nbPerson, int $nbDays, TaxeDetail $taxeDetail): float
    {
        $result = 0;
        $txMontant = $taxeDetail->montantIsFix() ? $taxeDetail->getTxMontant() : ($priceHt * $taxeDetail->getTxMontant() / 100);
        switch ($taxeDetail->getTxFormule()) {
            case TaxeDetail::BY_STAY_TAX:
                $result = $txMontant;
                break;
            case TaxeDetail::BY_NIGHT_TAX:
                $result = $txMontant * $nbDays;
                break;
            case TaxeDetail::BY_NIGHT_AND_PERSON_TAX:
                $result = $txMontant * $nbPerson * $nbDays;
                break;
            case TaxeDetail::BY_PERSON_TAX:
                $result = $txMontant * $nbPerson;
                break;
            default:
                throw new \LogicException("Error Calcule taxe: Type formule \"{$taxeDetail->getTxFormule()}\" not exists" );
        }
        return $result;
    }

}
