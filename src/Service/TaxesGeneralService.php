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
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
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
            if (!isset($t['id'], $t['taxe_type'], $t['taxe_value'])) {
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
            if ($eff['taxe_type'] === '%') {
                $base = 1.0;
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
                $amount = $base * ($eff['taxe_value'] / 100.0);
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
            if ($eff['taxe_type'] === '%') {
                $base = 0.0;
                foreach ($t['taxe_cumul'] as $depId) {
                    $base += $amounts[$depId];
                }
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
