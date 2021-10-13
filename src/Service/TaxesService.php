<?php

namespace Hi\Service;

use Hi\Model\TaxeDetail;
use Hi\Model\Tax;

/**
 * Service Taxes Hi
 *
 * @author Essahel Adil <adil.essahel@uncubus.com> 
 */
class TaxesService
{

    use \Hi\Traits\SimilarFieldsCopier;

    /**
     * Les taxes a partir de prix HT
     * 
     * @param float $priceHt Price HT (prix sans les taxes inculs et exclus)
     * @param array $taxes Les taxes format standard
     * @param int $nbPerson Nombre des personnes
     * @param int $nbDays  Nombre des jours de reservation
     * @return Tax
     */
    public function getDetailPricesFromPriceHT(float $priceHt, array $taxes, int $nbPerson, int $nbDays): Tax
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
        $sale = $priceHt + $totalTaxInc;

        return $this->copyTo((object) [
                    'priceTTC' => $ttc,
                    'priceHT' => $priceHt,
                    'priceSale' => $sale,
                    'totalTaxExc' => $totalTaxExc,
                    'totalTaxInc' => $totalTaxInc,
                    'detailTax' => ['inculded' => $detailTax[$inc], 'excluded' => $detailTax[$exc]],
                ], Tax::class);
    }

    /**
     * Prix HT a partir de prix de vente
     * 
     * @param float $priceSale
     * @param array $taxes
     * @param int $nbPerson
     * @param int $nbDays
     * @return float
     */
    public function getPriceHTFromPriceSale(float $priceSale, array $taxes, int $nbPerson, int $nbDays): float
    {
        return $this->_getPriceHT(false, $priceSale, $taxes, $nbPerson, $nbDays);
    }

    /**
     * Les taxes a partir de prix de vente
     * 
     * @param float $priceSale
     * @param array $taxes
     * @param int $nbPerson
     * @param int $nbDays
     * @return Tax
     */
    public function getDetailPricesFromPriceSale(float $priceSale, array $taxes, int $nbPerson, int $nbDays): Tax
    {
        $prixHt = $this->_getPriceHT(false, $priceSale, $taxes, $nbPerson, $nbDays);
        return $this->getDetailPricesFromPriceHT($prixHt, $taxes, $nbPerson, $nbDays);
    }

    /**
     * Les taxes a partir de prix TTC
     * 
     * @param float $priceTTC
     * @param array $taxes
     * @param int $nbPerson
     * @param int $nbDays
     * @return Tax
     */
    public function getDetailPricesFromPriceTTC(float $priceTTC, array $taxes, int $nbPerson, int $nbDays): Tax
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

    /**
     * get price HT from Price TTC or Price Sale
     * @param bool $isPriceTTC price is TTC : true else  fale
     * @param float $priceTTC
     * @param array $taxes
     * @param int $nbPerson
     * @param int $nbDays
     * @return float
     */
    private function _getPriceHT(bool $isPriceTTC, float $priceTTC, array $taxes, int $nbPerson, int $nbDays): float
    {
        $taxeFixe = 0;
        $chiffreTaxePoucentage = 0;
        foreach ($taxes as $taxe) {
            $detail = $this->convertToObject($taxe, TaxeDetail::class);
            $taxNbrOcc = $this->_getTaxNbrOcc($detail->getTxFormule(), $nbPerson, $nbDays);
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
        $result = $txMontant * $this->_getTaxNbrOcc($taxeDetail->getTxFormule(), $nbPerson, $nbDays);

        return $result;
    }

    private function _getTaxNbrOcc($txFormule, $nbPerson, $nbDays): int
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
