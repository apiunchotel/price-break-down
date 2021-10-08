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
        $detailTax = [1 => [], 0 => []];
        foreach ($taxes as $taxe) {
            $detail = $this->convertToObject($taxe, TaxeDetail::class);
            $montant = $this->_calculMontantTaxe($priceHt, $nbPerson, $nbDays, $detail);
            $details[(int) $detail->getTxInc()] += $montant;
            $detailTax[(int) $detail->getTxInc()][$detail->getTxName()] = $montant;
        }
        $ttc = $priceHt + array_sum($details);
        $ht = $priceHt;
        $sale = $priceHt + $details[$inc];

        return [
            'priceTTC' => $ttc,
            'priceHT' => $ht,
            'priceSale' => $sale,
            'totalTaxExc' => $details[$exc],
            'totalTaxInc' => $details[$inc],
            'detailTax' => $detailTax,
        ];
    }

    public function getPriceHTFromSale(float $priceSale, array $taxes, int $nbPerson, int $nbDays): float
    {
        $taxeFixe = 0;
        $chiffreTaxePoucentage = 0;
        foreach ($taxes as $taxe) {
            $detail = $this->convertToObject($taxe, TaxeDetail::class);
            if ($detail->getTxInc()) {
                $taxNbrOcc = $this->getTaxNbrOcc($detail->getTxFormule(), $nbPerson, $nbDays);
                if ($detail->montantIsFix()) {
                    $taxeFixe += $detail->getTxMontant() * $taxNbrOcc;
                } else {
                    $chiffreTaxePoucentage += $detail->getTxMontant() * $taxNbrOcc;
                }
            }
        }
        if ($chiffreTaxePoucentage == 0 && $taxeFixe == 0) {
            return $priceSale;
        }
        if ($chiffreTaxePoucentage == 0 && $taxeFixe > 0) {
            return $priceSale - $taxeFixe;
        }
        //dump("((100 * ($priceSale - $taxeFixe)) / $chiffreTaxePoucentage) / (1 + (100 / $chiffreTaxePoucentage))");
        $ht = ((100 * ($priceSale - $taxeFixe)) / $chiffreTaxePoucentage) / (1 + (100 / $chiffreTaxePoucentage));
        //dump("priceSale => $priceSale|HT => $ht");
        return $ht;
    }

    public function getTaxeFromSale(float $priceSale, array $taxes, int $nbPerson, int $nbDays): array
    {
        $prixHt = $this->getPriceHTFromSale($priceSale, $taxes, $nbPerson, $nbDays);
        return $this->getTaxeFromHt($prixHt, $taxes, $nbPerson, $nbDays);
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
        $txMontant = $taxeDetail->montantIsFix() ? $taxeDetail->getTxMontant() : ($priceHt * $taxeDetail->getTxMontant() / 100);
        $result = $txMontant * $this->getTaxNbrOcc($taxeDetail->getTxFormule(), $nbPerson, $nbDays);

        return $result;
    }

    public static function getTaxNbrOcc($txFormule, $nbPerson, $nbDays): int
    {
        $result = 1;
        switch ($txFormule) {
            case TaxeDetail::BY_STAY_TAX:
                $result = 1;
                break;
            case TaxeDetail::BY_NIGHT_TAX:
                $result = $nbDays;
                break;
            case TaxeDetail::BY_NIGHT_AND_PERSON_TAX:
                $result = $nbPerson * $nbDays;
                break;
            case TaxeDetail::BY_PERSON_TAX:
                $result = $nbPerson;
                break;
            default:
                throw new \LogicException("Error Calcule taxe: Type formule \"{$txFormule}\" not exists");
        }
        return $result;
    }

}
