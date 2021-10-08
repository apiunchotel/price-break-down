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
    public function getDetailPricesFromPriceHT(float $priceHt, array $taxes, int $nbPerson, int $nbDays): array
    {
        $inc = (int) true;
        $exc = (int) false;
        $detailTax = [$inc => [], $exc => []];
        foreach ($taxes as $taxe) {
            $detail = $this->convertToObject($taxe, TaxeDetail::class);
            $montant = $this->_calculMontantTaxe($priceHt, $nbPerson, $nbDays, $detail);
            $detailTax[(int) $detail->getTxInc()][$detail->getTxName()] = $montant;
        }
        $totalTaxExc = array_sum($detailTax[$exc]);
        $totalTaxInc = array_sum($detailTax[$inc]);
        $ttc = $priceHt + $totalTaxExc + $totalTaxInc;
        $ht = $priceHt;
        $sale = $priceHt + $totalTaxInc;

        return [
            'priceTTC' => $ttc,
            'priceHT' => $ht,
            'priceSale' => $sale,
            'totalTaxExc' => $totalTaxExc,
            'totalTaxInc' => $totalTaxInc,
            'detailTax' => ['inculded' => $detailTax[$inc], 'excluded' => $detailTax[$exc]],
        ];
    }

    public function getPriceHTFromPriceSale(float $priceSale, array $taxes, int $nbPerson, int $nbDays): float
    {
        return $this->_getPriceHT(false, $priceSale, $taxes, $nbPerson, $nbDays);
    }

    public function getDetailPricesFromPriceSale(float $priceSale, array $taxes, int $nbPerson, int $nbDays): array
    {
        $prixHt = $this->_getPriceHT(false, $priceSale, $taxes, $nbPerson, $nbDays);
        return $this->getDetailPricesFromPriceHT($prixHt, $taxes, $nbPerson, $nbDays);
    }

    public function getDetailPricesFromPriceTTC(float $priceTTC, array $taxes, int $nbPerson, int $nbDays): array
    {
        $prixHt = $this->_getPriceHT(true, $priceTTC, $taxes, $nbPerson, $nbDays);
        return $this->getDetailPricesFromPriceHT($prixHt, $taxes, $nbPerson, $nbDays);
    }

    public function getPriceHTFromPriceTTC(float $priceTTC, array $taxes, int $nbPerson, int $nbDays): float
    {
        return $this->_getPriceHT(true, $priceTTC, $taxes, $nbPerson, $nbDays);
    }

    public function convertToObject(array $taxe): TaxeDetail
    {
        return $this->copyTo((object) $taxe, TaxeDetail::class);
    }

    private function _getPriceHT(bool $isPriceTTC, float $priceTTC, array $taxes, int $nbPerson, int $nbDays): float
    {
        $taxeFixe = 0;
        $chiffreTaxePoucentage = 0;
        foreach ($taxes as $taxe) {
            $detail = $this->convertToObject($taxe, TaxeDetail::class);
            $taxNbrOcc = $this->getTaxNbrOcc($detail->getTxFormule(), $nbPerson, $nbDays);
            if ($detail->getTxInc() || $isPriceTTC) {
                if ($detail->montantIsFix()) {
                    $taxeFixe += $detail->getTxMontant() * $taxNbrOcc;
                } else {
                    $chiffreTaxePoucentage += $detail->getTxMontant() * $taxNbrOcc;
                }
            }
        }
        if ($chiffreTaxePoucentage == 0 && $taxeFixe == 0) {
            return $priceTTC;
        }
        if ($chiffreTaxePoucentage == 0 && $taxeFixe > 0) {
            return $priceTTC - $taxeFixe;
        }
        $ht = ((100 * ($priceTTC - $taxeFixe)) / $chiffreTaxePoucentage) / (1 + (100 / $chiffreTaxePoucentage));
        return $ht;
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
