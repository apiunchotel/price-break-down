<?php

namespace Apiunchotel\PriceBreakDown\Service;

use Apiunchotel\PriceBreakDown\Model\TaxeDetail;
use Apiunchotel\PriceBreakDown\Model\Tax;


/**
 * Service Taxes Hi
 *
// =========================================================================
// === NOUVELLE VERSION DU CALCUL DES TAXES (Breakdown V2 avec règles dynamiques)
// =========================================================================

 * Nouvelle version - Décompose TTC → HT avec gestion de règles dynamiques
 * @author Mounir el ouatiq <mounir.elouatiq@uncubus.com> 
 */


class TaxesGeneralService
{

    use \Apiunchotel\PriceBreakDown\Traits\SimilarFieldsCopier;


    /**
     * Les taxes a partir de prix HT
     * @deprecated Utilisez breakdownFromHTv2() à la place
     *
     * @param float $priceHt
     * @param array $taxes
     * @param integer $nbPerson
     * @param integer $nbDays
     * @param array $context
     * @return Tax
     */
    public function getDetailPricesFromPriceHT(float $priceHt, array $taxes, int $nbPerson, int $nbDays, array $context = []): Tax
    {
        return $this->breakdownFromHTv2($priceHt, $taxes, $nbPerson, $nbDays, $context);
    }

    /**
     * Les taxes a partir de prix de vente
     * @deprecated Utilisez breakdownFromSalePriceV2() à la place
     * 
     * @param float $priceSale
     * @param array $taxes
     * @param integer $nbPerson
     * @param integer $nbDays
     * @param array $context
     * @return Tax
     */
    public function getDetailPricesFromPriceSale(float $priceSale, array $taxes, int $nbPerson, int $nbDays, array $context = []): Tax
    {
        return $this->breakdownFromSalePriceV2($priceSale, $taxes, $nbPerson, $nbDays, $context);
    }

    /**
     * Les taxes a partir de prix TTC
     * @deprecated Utilisez breakdownFromTTCv2() à la place
     *
     * @param float $priceTTC
     * @param array $taxes
     * @param integer $nbPerson
     * @param integer $nbDays
     * @param array $context
     * @return Tax
     */
    public function getDetailPricesFromPriceTTC(float $priceTTC, array $taxes, int $nbPerson, int $nbDays, array $context = []): Tax
    {
        return $this->breakdownFromTTCv2($priceTTC, $taxes, $nbPerson, $nbDays, $context);
    }


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

            if ($eff['txTypeMontant'] === 0) {
                $base = $ht;
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
                $amount = $base * ($eff['txMontant'] / 100.0);
            } else {
                $amount = $this->fixedAmount([
                    'txMontant' => $eff['txMontant'],
                    'txFormule'   => $eff['txFormule']
                ], $persons, $nights);
            }

            $amounts[$id] = $amount;
            $running += $amount;

            if (!empty($t['txInc'])) $totalIncluded += $amount;
            else $totalExcluded += $amount;

            $lines[$t['id']] = [
                'id'     => $t['id'],
                'txName'   => $t['txName'] ?? '',
                'txTotalMontant' => $amount,
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
            'txTypeMontant' => $tax['txTypeMontant'],
            'txMontant' => (float)$tax['txMontant'],
            'txFormule'  => $tax['txTypeMontant'] === 1 ? ($tax['txFormule'] ?? 'BY_STAY_TAX') : null,
        ];

        if (!isset($tax['rule']) || !is_array($tax['rule'])) {
            return $effective;
        }

        $rule = $tax['rule'];
        if (($rule['type'] ?? null) === 'tax_value_change_on_avg_price_per_night') {
            $avg = (float)($context['avg_price_per_night'] ?? 0);
            $threshold = (float)($rule['threshold'] ?? 0);
            $branch = ($avg > $threshold) ? ($rule['above'] ?? null) : ($rule['below'] ?? null);

            if (isset($branch['txTypeMontant'], $branch['txMontant'])) {
                $effective['txTypeMontant'] = $branch['txTypeMontant'];
                $effective['txMontant'] = (float)$branch['txMontant'];
                $effective['txFormule'] = $branch['txFormule'] ?? $effective['txFormule'];
            }
        }

        return $effective;
    }

    private function fixedAmount(array $tax, int $persons, int $nights): float
    {
        $v = (float)$tax['txMontant'];
        switch ($tax['txFormule'] ?? 'BY_STAY_TAX') {
            case 'BY_STAY_TAX':
                return $v;
            case 'BY_PERSON_TAX':
                return $v * max(0, $persons);
            case 'BY_NIGHT_TAX':
                return $v * max(0, $nights);
            case 'BY_NIGHT_AND_PERSON_TAX':
                return $v * max(0, $persons) * max(0, $nights);
            default:
                return 0.0;
        }
    }

    private function topoOrder(array $taxes): array
    {
        $byId = [];
        foreach ($taxes as $t) {
            if (!isset($t['id'], $t['txTypeMontant'], $t['txMontant'])) {
                throw new \InvalidArgumentException("Tax missing required keys.");
            }
            if (!isset($t['taxe_cumul'])) $t['taxe_cumul'] = [];
            $byId[$t['id']] = $t;
        }

        $visited = $temp = $order = [];
        $visit = function ($id) use (&$visit, &$visited, &$temp, &$order, $byId) {
            if (isset($visited[$id])) return;
            if (isset($temp[$id])) throw new \RuntimeException("Cycle de dépendances détecté.");
            $temp[$id] = true;
            foreach ($byId[$id]['taxe_cumul'] as $depId) {
                $visit($depId);
            }
            unset($temp[$id]);
            $visited[$id] = true;
            $order[] = $id;
        };

        foreach ($byId as $id => $_) {
            $visit($id);
        }
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
        $amounts = [];
        $sum = 1.0;
        foreach ($order as $id) {
            $t = $byId[$id];
            $eff = $this->resolveTaxEffectiveWithForcedBranch($t, $forceBranch);
            if ($eff['txTypeMontant'] === 0) {
                $base = 1.0;
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
                $amount = $base * ($eff['txMontant'] / 100.0);
            } else {
                $amount = 0.0;
            }
            $amounts[$id] = $amount;
            $sum += $amount;
        }
        $a = $sum;

        // Étape B - HT=0, fixes
        $amounts = [];
        $sum = 0.0;
        foreach ($order as $id) {
            $t = $byId[$id];
            $eff = $this->resolveTaxEffectiveWithForcedBranch($t, $forceBranch);
            if ($eff['txTypeMontant'] === 0) {
                $base = 0.0;
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
                $amount = $base * ($eff['txMontant'] / 100.0);
            } else {
                $amount = $this->fixedAmount(['txMontant' => $eff['txMontant'], 'txFormule' => $eff['txFormule']], $persons, $nights);
            }
            $amounts[$id] = $amount;
            $sum += $amount;
        }
        $b = $sum;

        return ($priceTTC - $b) / $a;
    }

    /**
     * Nouvelle méthode - Décompose le prix de vente (HT + taxes incluses)
     * pour obtenir le même résultat que breakdownFromTTCv2()
     */
    public function breakdownFromSalePriceV2(float $priceSale, array $taxes, int $persons = 1, int $nights = 1, array $context = []): Tax
    {
        // Étape 1 : Calculer le HT à partir du prix de vente (HT + taxes incluses)
        $ht = $this->calculateHTFromSalePriceV2($priceSale, $taxes, $persons, $nights, $context);
        // Étape 2 : Reconstituer le breakdown complet (comme pour breakdownFromTTCv2)
        return $this->breakdownFromHTv2($ht, $taxes, $persons, $nights, $context);
    }

    /**
     * Calcule le prix HT à partir du prix de vente (HT + taxes incluses)
     * Compatible avec les clés "txInc" et "txFormule"
     */
    private function calculateHTFromSalePriceV2(float $priceSale, array $taxes, int $persons = 1, int $nights = 1, array $context = []): float
    {
        // 🔹 Garde uniquement les taxes incluses (txInc = true)
        $includedTaxes = array_filter($taxes, fn($t) => !empty($t['txInc']));

        // Aucun taxe incluse → prix HT = prix de vente
        if (empty($includedTaxes)) {
            return $priceSale;
        }

        [$byId, $order] = $this->topoOrder($includedTaxes);

        // --- Étape A : HT = 1 (taux proportionnels uniquement)
        $amounts = [];
        $sum = 1.0;
        foreach ($order as $id) {
            $t = $byId[$id];
            $eff = $this->resolveTaxEffective($t, $context);

            if ($eff['txTypeMontant'] === 0) {
                $base = 1.0;
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
                $amount = $base * ($eff['txMontant'] / 100.0);
            } else {
                $amount = 0.0; // Les montants fixes sont traités ensuite
            }
            $amounts[$id] = $amount;
            $sum += $amount;
        }
        $a = $sum;

        // --- Étape B : HT = 0 (montants fixes uniquement)
        $amounts = [];
        $sum = 0.0;
        foreach ($order as $id) {
            $t = $byId[$id];
            $eff = $this->resolveTaxEffective($t, $context);

            if ($eff['txTypeMontant'] === 0) {
                $base = 0.0;
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
                $amount = $base * ($eff['txMontant'] / 100.0);
            } else {
                // 🔹 Remplace fix_unit par txFormule
                $amount = $this->fixedAmount(
                    ['txMontant' => $eff['txMontant'], 'txFormule' => $eff['txFormule'] ?? 'BY_STAY_TAX'],
                    $persons,
                    $nights
                );
            }
            $amounts[$id] = $amount;
            $sum += $amount;
        }
        $b = $sum;

        // Formule : PrixVente = HT * a + b  →  HT = (PrixVente - b) / a
        return ($priceSale - $b) / $a;
    }



    private function resolveTaxEffectiveWithForcedBranch(array $tax, string $forceBranch): array
    {
        if (!isset($tax['rule']) || $tax['rule']['type'] !== 'tax_value_change_on_avg_price_per_night') {
            return [
                'txTypeMontant' => $tax['txTypeMontant'],
                'txMontant' => (float)$tax['txMontant'],
                'txFormule'  => $tax['txTypeMontant'] === 1 ? ($tax['txFormule'] ?? 'BY_STAY_TAX') : null,
            ];
        }

        $branch = $tax['rule'][$forceBranch] ?? null;
        if (!is_array($branch)) {
            throw new \InvalidArgumentException("Missing branch $forceBranch in rule.");
        }

        return [
            'txTypeMontant' => $branch['txTypeMontant'],
            'txMontant' => (float)$branch['txMontant'],
            'txFormule'  => $branch['txFormule'] ?? ($tax['txFormule'] ?? 'BY_STAY_TAX'),
        ];
    }
}
