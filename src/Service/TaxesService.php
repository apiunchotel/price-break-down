<?php

namespace Apiunchotel\PriceBreakDown\Service;

use Apiunchotel\PriceBreakDown\Model\TaxeDetail;
use Apiunchotel\PriceBreakDown\Model\Tax;

/**
 * Service Taxes Hi
 *
 * @author Essahel Adil <adil.essahel@uncubus.com> 
 */
class TaxesService
{

    use \Apiunchotel\PriceBreakDown\Traits\SimilarFieldsCopier;

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
        $originalTaxes = [];
        foreach ($taxes as $taxe) {
            $detail = $this->convertToObject($taxe, TaxeDetail::class);
            $montant = $this->_calculMontantTaxe($priceHt, $nbPerson, $nbDays, $detail);
            $detailTax[(int) $detail->getTxInc()][$detail->getTxName()] = $montant;
            $originalTaxes[] = $detail->toArray();
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
                    'originalTaxes' => $originalTaxes,
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
        $sourceObjectReflection = new \ReflectionClass(TaxeDetail::class);
        foreach ($sourceObjectReflection->getProperties() as $sourceProperty) {
            /* @var $sourceProperty \ReflectionProperty */
            $nameProprety = $sourceProperty->getName();
            if (!array_key_exists($nameProprety, $taxe) && !in_array($nameProprety, ['txOta'])) {
                throw new \LogicException("Error Property Tax \"{$nameProprety}\" not exists !");
            }
        }
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
        return round($ht, 6);
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


        // =========================================================================
    // === NOUVELLE VERSION DU CALCUL DES TAXES (Breakdown V2 avec règles dynamiques)
    // =========================================================================

    /**
     * Nouvelle version - Décompose TTC → HT avec gestion de règles dynamiques
     */
    public function breakdownFromTTCv2(float $priceTTC, array $taxes, int $persons = 1, int $nights = 1, array $context = []): Tax
    {
        $ht = $this->calculateHTClosedFormV2($priceTTC, $taxes, $persons, $nights, $context);
        return $this->breakdownFromHTv2($ht, $taxes, $persons, $nights, $context);
    }

    /**
     * Nouvelle version - Décompose HT → TTC avec cumul des taxes et règles conditionnelles
     */
    public function breakdownFromHTv2(float $ht, array $taxes, int $persons = 1, int $nights = 1, array $context = []): Tax
    {
        [$byId, $order] = $this->topoOrder($taxes);

        $amounts = [];
        $lines   = [];
        $running = $ht;
        $totalIncluded = $totalExcluded = 0;

        foreach ($order as $id) {
            $t = $byId[$id];
            $context['avg_price_per_night'] = ($nights > 0) ? $ht / $nights : 0;
            $eff = $this->resolveTaxEffective($t, $context);

            if ($eff['taxe_type'] === '%') {
                $base = $ht;
                foreach ($t['taxe_cumul'] as $depId) { $base += $amounts[$depId]; }
                $amount = $base * ($eff['taxe_value'] / 100.0);
            } else {
                $amount = $this->fixedAmount([
                    'taxe_value' => $eff['taxe_value'],
                    'fix_unit'   => $eff['fix_unit']
                ], $persons, $nights);
            }

            $amounts[$id] = $amount;
            $running += $amount;

            if (!empty($t['included'])) $totalIncluded += $amount;
            else $totalExcluded += $amount;

            $lines[$t['id']] = [
                'id'     => $t['id'],
                'name'   => $t['taxe_name'] ?? '',
                'type'   => $eff['taxe_type'],
                'unit'   => $eff['fix_unit'] ?? null,
                'rate'   => $eff['taxe_value'] ?? null,
                'amount' => $amount,
            ];
        }

        return (new Tax())
            ->setPriceHT($ht)
            ->setPriceSale($running - $totalExcluded)
            ->setPriceTTC($running)
            ->setTotalTaxExc($totalExcluded)
            ->setTotalTaxInc($totalIncluded)
            ->setDetailTax($lines)
            ->setOriginalTaxes($taxes);
    }

    // === Fonctions internes V2 ===

    private function resolveTaxEffective(array $tax, array $context): array
    {
        $effective = [
            'taxe_type' => $tax['taxe_type'],
            'taxe_value' => (float)$tax['taxe_value'],
            'fix_unit'  => $tax['taxe_type'] === 'fix' ? ($tax['fix_unit'] ?? 'BY_STAY_TAX') : null,
        ];

        if (!isset($tax['rule']) || !is_array($tax['rule'])) {
            return $effective;
        }

        $rule = $tax['rule'];
        if (($rule['type'] ?? null) === 'tax_value_change_on_avg_price_per_night') {
            $avg = (float)($context['avg_price_per_night'] ?? 0);
            $threshold = (float)($rule['threshold'] ?? 0);
            $branch = ($avg > $threshold) ? ($rule['above'] ?? null) : ($rule['below'] ?? null);

            if (isset($branch['taxe_type'], $branch['taxe_value'])) {
                $effective['taxe_type'] = $branch['taxe_type'];
                $effective['taxe_value'] = (float)$branch['taxe_value'];
                $effective['fix_unit'] = $branch['fix_unit'] ?? $effective['fix_unit'];
            }
        }

        return $effective;
    }

    private function fixedAmount(array $tax, int $persons, int $nights): float
    {
        $v = (float)$tax['taxe_value'];
        switch ($tax['fix_unit'] ?? 'BY_STAY_TAX') {
            case 'BY_STAY_TAX': return $v;
            case 'BY_PERSON_TAX': return $v * max(0, $persons);
            case 'BY_NIGHT_TAX': return $v * max(0, $nights);
            case 'BY_NIGHT_AND_PERSON_TAX': return $v * max(0, $persons) * max(0, $nights);
            default: return 0.0;
        }
    }

    private function topoOrder(array $taxes): array
    {
        $byId = [];
        foreach ($taxes as $t) {
            if (!isset($t['id'], $t['taxe_type'], $t['taxe_value'])) {
                throw new \InvalidArgumentException("Tax missing required keys.");
            }
            if (!isset($t['taxe_cumul'])) $t['taxe_cumul'] = [];
            $byId[$t['id']] = $t;
        }

        $visited = $temp = $order = [];
        $visit = function($id) use (&$visit, &$visited, &$temp, &$order, $byId) {
            if (isset($visited[$id])) return;
            if (isset($temp[$id])) throw new \RuntimeException("Cycle de dépendances détecté.");
            $temp[$id] = true;
            foreach ($byId[$id]['taxe_cumul'] as $depId) { $visit($depId); }
            unset($temp[$id]);
            $visited[$id] = true;
            $order[] = $id;
        };

        foreach ($byId as $id => $_) { $visit($id); }
        return [$byId, $order];
    }

    private function calculateHTClosedFormV2(float $priceTTC, array $taxes, int $persons, int $nights, array $context = []): float
    {
        $htCandidate = $this->calculateHTGivenBranch($priceTTC, $taxes, $persons, $nights, 'below');
        $avg = ($nights > 0) ? $htCandidate / $nights : 0;
        $branch = $this->determineBranch($taxes, $avg);
        if ($branch === 'below') return $htCandidate;
        return $this->calculateHTGivenBranch($priceTTC, $taxes, $persons, $nights, 'above');
    }

    private function determineBranch(array $taxes, float $avg): string
    {
        foreach ($taxes as $t) {
            if (isset($t['rule']['type']) && $t['rule']['type'] === 'tax_value_change_on_avg_price_per_night') {
                $threshold = (float)($t['rule']['threshold'] ?? 0);
                return ($avg > $threshold) ? 'above' : 'below';
            }
        }
        return 'below';
    }

    private function calculateHTGivenBranch(float $priceTTC, array $taxes, int $persons, int $nights, string $forceBranch): float
    {
        [$byId, $order] = $this->topoOrder($taxes);

        // Étape A - HT=1
        $amounts = []; $sum = 1.0;
        foreach ($order as $id) {
            $t = $byId[$id];
            $eff = $this->resolveTaxEffectiveWithForcedBranch($t, $forceBranch);
            if ($eff['taxe_type'] === '%') {
                $base = 1.0;
                foreach ($t['taxe_cumul'] as $depId) { $base += $amounts[$depId]; }
                $amount = $base * ($eff['taxe_value'] / 100.0);
            } else {
                $amount = 0.0;
            }
            $amounts[$id] = $amount;
            $sum += $amount;
        }
        $a = $sum;

        // Étape B - HT=0, fixes
        $amounts = []; $sum = 0.0;
        foreach ($order as $id) {
            $t = $byId[$id];
            $eff = $this->resolveTaxEffectiveWithForcedBranch($t, $forceBranch);
            if ($eff['taxe_type'] === '%') {
                $base = 0.0;
                foreach ($t['taxe_cumul'] as $depId) { $base += $amounts[$depId]; }
                $amount = $base * ($eff['taxe_value'] / 100.0);
            } else {
                $amount = $this->fixedAmount(['taxe_value' => $eff['taxe_value'], 'fix_unit' => $eff['fix_unit']], $persons, $nights);
            }
            $amounts[$id] = $amount;
            $sum += $amount;
        }
        $b = $sum;

        return ($priceTTC - $b) / $a;
    }

    private function resolveTaxEffectiveWithForcedBranch(array $tax, string $forceBranch): array
    {
        if (!isset($tax['rule']) || $tax['rule']['type'] !== 'tax_value_change_on_avg_price_per_night') {
            return [
                'taxe_type' => $tax['taxe_type'],
                'taxe_value' => (float)$tax['taxe_value'],
                'fix_unit'  => $tax['taxe_type'] === 'fix' ? ($tax['fix_unit'] ?? 'BY_STAY_TAX') : null,
            ];
        }

        $branch = $tax['rule'][$forceBranch] ?? null;
        if (!is_array($branch)) {
            throw new \InvalidArgumentException("Missing branch $forceBranch in rule.");
        }

        return [
            'taxe_type' => $branch['taxe_type'],
            'taxe_value' => (float)$branch['taxe_value'],
            'fix_unit'  => $branch['fix_unit'] ?? ($tax['fix_unit'] ?? 'BY_STAY_TAX'),
        ];
    }


}
