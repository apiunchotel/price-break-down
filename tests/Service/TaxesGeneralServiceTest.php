<?php

namespace Apiunchotel\PriceBreakDown\Tests\Service;

use Apiunchotel\PriceBreakDown\Service\TaxesService;
use PHPUnit\Framework\TestCase;

/**
 * Test de validation internationale du moteur de calcul des taxes (V2)
 *
 * Ces tests vérifient la cohérence du calcul des montants TTC, HT, PV et taxes
 * pour plusieurs cas réels (Bahreïn, Inde, Maroc, etc.)
 *
 * @author Mounir El ouatiq
 */
class TaxesServiceV2ExamplesTest extends TestCase
{
    /**
     * @dataProvider examplesProvider
     */
    public function testBreakdownFromTTCExamples(
        string $country,
        float $expectedTTC,
        float $expectedHT,
        float $expectedPV,
        int $persons,
        int $nights,
        array $taxes
    ): void {
        $service = new TaxesService();

        $result = $service->breakdownFromTTCv2($expectedTTC, $taxes, $persons, $nights);
        $data = $result->toArray();

        // On tolère ±0.1 de différence à cause des arrondis
        $delta = 0.1;

        $this->assertEqualsWithDelta($expectedTTC, $data['priceTTC'], $delta, "$country - TTC incorrect");
        $this->assertEqualsWithDelta($expectedHT, $data['priceHT'], $delta, "$country - HT incorrect");
        $this->assertEqualsWithDelta($expectedPV, $data['priceSale'], $delta, "$country - PV incorrect");
    }

    public function examplesProvider(): array
    {
        $ex = [];

        // -------------------- Exemple Bahrain --------------------
        $ex["bahrain"] = [
            'bahrain', 169.09, 129.89, 129.89, 2, 1, [
                ["id" => 1, "taxe_name" => "service charge", "taxe_type" => "%", "taxe_value" => 10, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 2, "taxe_name" => "delivery taxe", "taxe_type" => "%", "taxe_value" => 5, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [1], "included" => false],
                ["id" => 3, "taxe_name" => "bed taxe", "taxe_type" => "fix", "taxe_value" => 3, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 4, "taxe_name" => "vat taxe", "taxe_type" => "%", "taxe_value" => 10.5, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [1, 2, 3], "included" => false],
            ]
        ];

        // -------------------- Exemple Bahrain 2 --------------------
        $ex["bahrain2"] = [
            'bahrain2', 367.01, 281.08, 281.08, 2, 3, [
                ["id" => 1, "taxe_name" => "service charge", "taxe_type" => "%", "taxe_value" => 10, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 2, "taxe_name" => "Tax City", "taxe_type" => "%", "taxe_value" => 5, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [1], "included" => false],
                ["id" => 3, "taxe_name" => "Accommodation fees", "taxe_type" => "fix", "taxe_value" => 3, "fix_unit" => "BY_NIGHT_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 4, "taxe_name" => "vat", "taxe_type" => "%", "taxe_value" => 10, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [1, 2, 3], "included" => false],
            ]
        ];

        // -------------------- Exemple Inde --------------------
        $ex["inde"] = [
            'Inde', 8414, 7000, 7000, 2, 1, [
                ["id" => 1, "taxe_name" => "service charge", "taxe_type" => "%", "taxe_value" => 8.2, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 2, "taxe_name" => "GST", "taxe_type" => "%", "taxe_value" => 12, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false, "rule" => [
                    "type" => "tax_value_change_on_avg_price_per_night",
                    "threshold" => 7500,
                    "below" => ["taxe_type" => "%", "taxe_value" => 12],
                    "above" => ["taxe_type" => "%", "taxe_value" => 18]
                ]]
            ]
        ];

        // -------------------- Exemple Maroc --------------------
        $ex["maroc"] = [
            'Maroc', 120.40, 100.00, 110.00, 2, 2, [
                ["id" => 1, "taxe_name" => "service charge", "taxe_type" => "fix", "taxe_value" => 1, "fix_unit" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 2, "taxe_name" => "Tax City", "taxe_type" => "fix", "taxe_value" => 1.6, "fix_unit" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 3, "taxe_name" => "VAT", "taxe_type" => "%", "taxe_value" => 10, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => true],
            ]
        ];

        // -------------------- Exemple France --------------------
        $ex["france"] = [
            'France', 216.96, 166.51, 183.16, 2, 2, [
                ["id" => 1, "taxe_name" => "Tax City", "taxe_type" => "fix", "taxe_value" => 8.45, "fix_unit" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 2, "taxe_name" => "VAT", "taxe_type" => "%", "taxe_value" => 10, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => true],
            ]
        ];

        // -------------------- Exemple Oman --------------------
        $ex["oman"] = [
            'Oman', 163.9491000, 133, 133, 2, 1, [
                ["id" => 1, "taxe_name" => "VAT", "taxe_type" => "%", "taxe_value" => 5, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [2,3,4], "included" => false],
                ["id" => 2, "taxe_name" => "Taxe touristique", "taxe_type" => "%", "taxe_value" => 4, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 3, "taxe_name" => "Tax municipale", "taxe_type" => "%", "taxe_value" => 5, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [4], "included" => false],
                ["id" => 4, "taxe_name" => "service fees", "taxe_type" => "%", "taxe_value" => 8, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
            ]
        ];

        // -------------------- Exemple Egypte --------------------
        $ex["egypt"] = [
            'Egypte', 88.79, 69.00, 69.00, 2, 1, [
                ["id" => 1, "taxe_name" => "Tax", "taxe_type" => "%", "taxe_value" => 14, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 2, "taxe_name" => "Tax City", "taxe_type" => "%", "taxe_value" => 1, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 3, "taxe_name" => "service fees", "taxe_type" => "%", "taxe_value" => 12, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [1], "included" => false],
            ]
        ];

        // -------------------- Exemple EAU --------------------
        $ex["eau"] = [
            'EAU', 848.26, 561.60, 561.60, 2, 1, [
                ["id" => 1, "taxe_name" => "Tax", "taxe_type" => "%", "taxe_value" => 5, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 2, "taxe_name" => "VAT", "taxe_type" => "%", "taxe_value" => 5, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [4,5], "included" => false],
                ["id" => 3, "taxe_name" => "Taxe touristique", "taxe_type" => "fix", "taxe_value" => 10, "fix_unit" => "BY_NIGHT_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 4, "taxe_name" => "Taxe destination", "taxe_type" => "fix", "taxe_value" => 10, "fix_unit" => "BY_NIGHT_TAX", "taxe_cumul" => [], "included" => false],
                ["id" => 5, "taxe_name" => "Frais ménage", "taxe_type" => "fix", "taxe_value" => 200, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => false],
            ]
        ];

        // -------------------- Exemple Afrique du Sud --------------------
        $ex["afrique_du_sud"] = [
            'Afrique du Sud', 1728.41, 1516.15, 1728.41, 2, 1, [
                ["id" => 1, "taxe_name" => "VAT", "taxe_type" => "%", "taxe_value" => 14, "fix_unit" => "BY_STAY_TAX", "taxe_cumul" => [], "included" => true],
            ]
        ];

        return array_values($ex);
    }
}
