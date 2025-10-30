<?php

namespace Apiunchotel\PriceBreakDown\Tests\Service;

use Apiunchotel\PriceBreakDown\Service\TaxesGeneralService;
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
    public function testBreakdownFromsaleExamples(
        string $country,
        float $expectedTTC,
        float $expectedHT,
        float $expectedPV,
        int $persons,
        int $nights,
        array $taxes
    ): void {
        $service = new TaxesGeneralService();

        $result = $service->breakdownFromSalePriceV2($expectedPV, $taxes, $persons, $nights);
        $data = $result->toArray();
        // On tolère ±0.1 de différence à cause des arrondis
        $delta = 0.1;

        $this->assertEqualsWithDelta($expectedTTC, $data['priceTTC'], $delta, "$country - TTC incorrect from SALE price ");
        $this->assertEqualsWithDelta($expectedHT, $data['priceHT'], $delta, "$country - HT incorrect from SALE price ");
        $this->assertEqualsWithDelta($expectedPV, $data['priceSale'], $delta, "$country - PV incorrect from SALE price ");
    }

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
        $service = new TaxesGeneralService();

        $result = $service->breakdownFromTTCv2($expectedTTC, $taxes, $persons, $nights);
        $data = $result->toArray();
        // dd($data);
        // On tolère ±0.1 de différence à cause des arrondis
        $delta = 0.1;

        $this->assertEqualsWithDelta($expectedTTC, $data['priceTTC'], $delta, "$country - TTC incorrect from TTC price");
        $this->assertEqualsWithDelta($expectedHT, $data['priceHT'], $delta, "$country - HT incorrect from TTC price");
        $this->assertEqualsWithDelta($expectedPV, $data['priceSale'], $delta, "$country - PV incorrect from TTC price");
    }
    
    public function examplesProvider(): array
    {
        $ex = [];



        // -------------------- Exemple afrique du sud --------------------
        $ex["afrique_du_sud"] = [
            'Afrique du Sud',
            1728.41,
            1516.15,
            1728.41,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 14, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        // -------------------- Exemple oman --------------------
        $ex["oman"] = [
            'Oman',
            163.949,
            133.0000,
            133.0000,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT ",  "txTypeMontant" => 0, "txMontant" => 5,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [2, 3, 4], "txInc" => false],
                ["id" => 2, "txName" => "Taxe touristique ",  "txTypeMontant" => 0, "txMontant" => 4,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "Tax municipale",      "txTypeMontant" => 0, "txMontant" => 5,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [4], "txInc" => false],
                ["id" => 4, "txName" => "service fees",        "txTypeMontant" => 0, "txMontant" => 8,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple bahrain --------------------
        $ex["bahrain"] = [
            'bahrain',
            169.09,
            129.89,
            129.89,
            2,
            1,
            [
                ["id" => 1, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 10,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "delivery taxe",  "txTypeMontant" => 0, "txMontant" => 5,    "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
                ["id" => 3, "txName" => "bed taxe",       "txTypeMontant" => 1, "txMontant" => 3,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 4, "txName" => "vat taxe",       "txTypeMontant" => 0, "txMontant" => 10.5, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1, 2, 3], "txInc" => false],
            ]
        ];

        // -------------------- Exemple bahrain 2 --------------------
        $ex["bahrain2"] = [
            'bahrain2',
            367.01,
            281.08,
            281.08,
            2,
            3,
            [
                ["id" => 1, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Tax City",       "txTypeMontant" => 0, "txMontant" => 5,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
                ["id" => 3, "txName" => "Accommodation fees", "txTypeMontant" => 1, "txMontant" => 3, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 4, "txName" => "vat",            "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1, 2, 3], "txInc" => false],
            ]
        ];

        // -------------------- Exemple bahrain 3 --------------------
        $ex["bahrain3"] = [
            'bahrain3',
            94.89,
            72.09,
            72.09,
            2,
            1,
            [
                ["id" => 1, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Tax City",       "txTypeMontant" => 0, "txMontant" => 5,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
                ["id" => 3, "txName" => "Accommodation fees", "txTypeMontant" => 1, "txMontant" => 3, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 4, "txName" => "vat",            "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1, 2, 3], "txInc" => false],
            ]
        ];

        // -------------------- Exemple bahrain 4 --------------------
        $ex["bahrain4"] = [
            'bahrain4',
            378.80,
            358.80,
            358.80,
            2,
            1,
            [
                ["id" => 1, "txName" => "Service Charge", "txTypeMontant" => 1, "txMontant" => 20, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple eau --------------------
        $ex["eau1"] = [
            'EAU',
            848.26,
            561.60,
            561.60,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax ",  "txTypeMontant" => 0,  "txMontant" => 5,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "VAT ",  "txTypeMontant" => 0,  "txMontant" => 5,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [4, 5], "txInc" => false],
                ["id" => 3, "txName" => "Taxe touristique", "txTypeMontant" => 1, "txMontant" => 10, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 4, "txName" => "Taxe destination", "txTypeMontant" => 1, "txMontant" => 10, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 5, "txName" => "Frais ménage",    "txTypeMontant" => 1, "txMontant" => 200, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple Inde --------------------
        $ex["Inde"] = [
            'Inde',
            8414.00,
            7000.00,
            7000.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "service charge", "txTypeMontant" => 0,   "txMontant" => 8.2, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Taxe sur les bien et services", "txTypeMontant" => 0, "txMontant" => 12, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false, "rule" => [
                    "type" => "tax_value_change_on_avg_price_per_night",
                    "threshold" => 7500,
                    "below" => ["txTypeMontant" => 0, "txMontant" => 12],
                    "above" => ["txTypeMontant" => 0, "txMontant" => 18]
                ]],
            ]
        ];

        // -------------------- Exemple Inde 4 --------------------
        $ex["Inde4"] = [
            'Inde4',
            3525.67,
            3056.85,
            3056.85,
            2,
            3,
            [
                ["id" => 2, "txName" => "Frais de service ", "txTypeMontant" => 1, "txMontant" => 34, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 1, "txName" => "Taxe sur les bien et services", "txTypeMontant" => 0, "txMontant" => 12, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false, "rule" => [
                    "type" => "tax_value_change_on_avg_price_per_night",
                    "threshold" => 7499,
                    "below" => ["txTypeMontant" => 0, "txMontant" => 12],
                    "above" => ["txTypeMontant" => 0, "txMontant" => 18]
                ]],
            ]
        ];


        // -------------------- Exemple maroc --------------------
        $ex["maroc1"] = [
            'Maroc1',
            120.40,
            100.00,
            110.00,
            2,
            2,
            [
                ["id" => 1, "txName" => "service charge", "txTypeMontant" => 1, "txMontant" => 1.0,  "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Tax City",       "txTypeMontant" => 1, "txMontant" => 1.6,  "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "VAT",            "txTypeMontant" => 0,  "txMontant" => 10,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["maroc2"] = [
            'Maroc2',
            116.40,
            100.00,
            110.00,
            2,
            2,
            [
                ["id" => 1, "txName" => "Tax City",  "txTypeMontant" => 1, "txMontant" => 1.6, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "VAT",       "txTypeMontant" => 0,  "txMontant" => 10,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["maroc3"] = [
            'Maroc3',
            64.80,
            51.6360,
            64.80,
            2,
            2,
            [
                ["id" => 1, "txName" => "Tax City", "txTypeMontant" => 1, "txMontant" => 2,  "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "VAT",      "txTypeMontant" => 0,  "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        // -------------------- Exemple france --------------------
        $ex["france1"] = [
            'France1',
            216.96,
            166.51,
            183.16,
            2,
            2,
            [
                ["id" => 1, "txName" => "Tax City", "txTypeMontant" => 1, "txMontant" => 8.45, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "VAT",      "txTypeMontant" => 0,  "txMontant" => 10,   "txFormule" => "BY_STAY_TAX ", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["france2"] = [
            'France2',
            223.70,
            219.30,
            219.30,
            2,
            2,
            [
                ["id" => 1, "txName" => "Tax City", "txTypeMontant" => 1, "txMontant" => 1.1, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple espagne --------------------
        $ex["espagne1"] = [
            'Espagne1',
            137.64,
            122.73,
            135.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax City", "txTypeMontant" => 1, "txMontant" => 1.32, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "VAT",      "txTypeMontant" => 0,  "txMontant" => 10,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["espagne2"] = [
            'Espagne2',
            404.60,
            367.82,
            404.60,
            2,
            3,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        // -------------------- Exemple egypt --------------------
        $ex["egypt"] = [
            'Egypte',
            88.79,
            69.00,
            69.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax ",     "txTypeMontant" => 0, "txMontant" => 14, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Tax City", "txTypeMontant" => 0, "txMontant" => 1,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "service fees", "txTypeMontant" => 0, "txMontant" => 12,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
            ]
        ];

        // -------------------- Exemple allemagne --------------------
        $ex["allemagne1"] = [
            'Allemagne1',
            87.0750,
            75.7009,
            81.0000,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax City", "txTypeMontant" => 0, "txMontant" => 7.5, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [2], "txInc" => false],
                ["id" => 2, "txName" => "VAT",      "txTypeMontant" => 0, "txMontant" => 7,   "txFormule" => "BY_STAY_TAX ", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["allemagne2"] = [
            'Allemagne2',
            269.00,
            251.40,
            269.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 7, "txFormule" => "BY_STAY_TAX ", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["allemagne3"] = [
            'Allemagne3',
            194.00,
            172.67,
            194.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax City", "txTypeMontant" => 0, "txMontant" => 5, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [2], "txInc" => true],
                ["id" => 2, "txName" => "VAT",      "txTypeMontant" => 0, "txMontant" => 7, "txFormule" => "BY_STAY_TAX ", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        // -------------------- Exemple canada --------------------
        $ex["canada1"] = [
            'Canada1',
            166.11,
            140.00,
            147.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax",               "txTypeMontant" => 0, "txMontant" => 13,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [2], "txInc" => false],
                ["id" => 2, "txName" => "Municipality fee",  "txTypeMontant" => 0, "txMontant" => 5,    "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["canada2"] = [
            'Canada2',
            968.9328,
            804.10,
            804.10,
            2,
            4,
            [
                ["id" => 1, "txName" => "tax",                "txTypeMontant" => 0, "txMontant" => 14.98, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [2, 3], "txInc" => false],
                ["id" => 2, "txName" => "environment fee",    "txTypeMontant" => 0, "txMontant" => 1.30, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "city tax",           "txTypeMontant" => 0, "txMontant" => 3.50, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["canada3"] = [
            'Canada3',
            458.39,
            385.20,
            385.20,
            2,
            2,
            [
                ["id" => 1, "txName" => "vat", "txTypeMontant" => 0, "txMontant" => 19, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple usa --------------------
        $ex["usa1"] = [
            'USA1',
            5855.0480,
            5020.00,
            5020.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax ", "txTypeMontant" => 0, "txMontant" => 13,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Taxe immobilière", "txTypeMontant" => 0, "txMontant" => 2, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "Taxe gouvernementale", "txTypeMontant" => 0, "txMontant" => 0.24, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 4, "txName" => "Frais de service", "txTypeMontant" => 1, "txMontant" => 70, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["usa2"] = [
            'USA2',
            650.93,
            539.00,
            539.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax ", "txTypeMontant" => 0, "txMontant" => 15.20, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Frais de service", "txTypeMontant" => 1, "txMontant" => 30, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["usa3"] = [
            'USA3',
            492.20,
            428.00,
            428.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax ", "txTypeMontant" => 0, "txMontant" => 15, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["usa4"] = [
            'USA4',
            651.23,
            420.29,
            420.29,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax ", "txTypeMontant" => 0, "txMontant" => 14.50, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "frais de menage", "txTypeMontant" => 1, "txMontant" => 155, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "frais de service", "txTypeMontant" => 1, "txMontant" => 15, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["usa5"] = [
            'USA5',
            437.2070,
            379.52,
            379.52,
            2,
            1,
            [
                ["id" => 1, "txName" => "Tax ", "txTypeMontant" => 0, "txMontant" => 13,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "taxe de sejour", "txTypeMontant" => 0, "txMontant" => 2, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "destination", "txTypeMontant" => 0, "txMontant" => 0.2, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["usa6"] = [
            'USA6',
            746.20,
            497.25,
            497.25,
            2,
            3,
            [
                ["id" => 1, "txName" => "Tax ", "txTypeMontant" => 0, "txMontant" => 11, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [2, 3], "txInc" => false],
                ["id" => 2, "txName" => "frais de menage", "txTypeMontant" => 1, "txMontant" => 100, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "destination", "txTypeMontant" => 1, "txMontant" => 25, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["usa7"] = [
            'USA7',
            502.96189,
            425.00,
            425.00,
            4,
            5,
            [
                ["id" => 1, "txName" => "Tax ", "txTypeMontant" => 0, "txMontant" => 7.75, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "frais gouvernementale", "txTypeMontant" => 0, "txMontant" => 0.78, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "frais service", "txTypeMontant" => 0, "txMontant" => 0.75, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1, 2], "txInc" => false],
                ["id" => 4, "txName" => "taxe de sejour", "txTypeMontant" => 0, "txMontant" => 9, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple tunisie --------------------
        $ex["tunisie"] = [
            'Tunisie',
            130.60,
            122.64,
            130.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 6, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "Tax City", "txTypeMontant" => 1, "txMontant" => 0.3, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple arabiesaoudite --------------------
        $ex["arabiesaoudite1"] = [
            'ArabieSaoudite1',
            298.655,
            259.70,
            259.70,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 15, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["arabiesaoudite2"] = [
            'ArabieSaoudite2',
            89950.2975,
            74493.0000,
            74493.0000,
            2,
            6,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 15, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "Tax municipality", "txTypeMontant" => 0, "txMontant" => 5, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
            ]
        ];

        // -------------------- Exemple senegal --------------------
        $ex["senegal"] = [
            'Sénégal',
            137000.00,
            122727.27,
            135000.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "Tax City", "txTypeMontant" => 1, "txMontant" => 1000, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple bresil --------------------
        $ex["bresil1"] = [
            'Brésil1',
            459.4485,
            437.57,
            437.57,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 5, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["bresil2"] = [
            'Brésil2',
            9813.18,
            8485.00,
            9813.18,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 5,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "property service", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => true],
                ["id" => 3, "txName" => "tourism fee",    "txTypeMontant" => 1, "txMontant" => 13, "txFormule" => "BY_NIGHT_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["bresil3"] = [
            'Brésil3',
            790.05,
            790.05,
            790.05,
            2,
            1,
            []
        ];

        // -------------------- Exemple thailande --------------------
        $ex["thailande1"] = [
            'Thaïlande1',
            4577.52,
            3889.1419,
            4577.52,
            3,
            3,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 7,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => true],
            ]
        ];

        $ex["thailande2"] = [
            'Thaïlande2',
            4189.50,
            3500.00,
            3500.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 7,   "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 10.7, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "city tax",      "txTypeMontant" => 0, "txMontant" => 2,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple uk --------------------
        $ex["uk1"] = [
            'UK1',
            55.00,
            45.8333,
            55.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 20, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["uk2"] = [
            'UK2',
            811.60,
            572.1670,
            686.60,
            2,
            2,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 20, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "tax city", "txTypeMontant" => 1, "txMontant" => 125, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple liban --------------------
        $ex["liban"] = [
            'Liban',
            98.1818,
            81.8182,
            90.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "tax city", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple philippines --------------------
        $ex["philippines"] = [
            'Philippines',
            7503.12,
            6120.00,
            6120.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 12,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "city tax", "txTypeMontant" => 0, "txMontant" => 0.6, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        // -------------------- Exemple jordanie --------------------
        $ex["jordanie1"] = [
            'Jordanie1',
            273.00,
            236.3636,
            260.00,
            3,
            2,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 5, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
            ]
        ];

        $ex["jordanie2"] = [
            'Jordanie2',
            347.4199,
            303.45,
            303.45,
            2,
            3,
            [
                ["id" => 1, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 7, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "city tax", "txTypeMontant" => 0, "txMontant" => 7, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
            ]
        ];

        // -------------------- Exemple zanzibar --------------------
        $ex["zanzibar1"] = [
            'Zanzibar1',
            329.62,
            241.2034,
            284.62,
            2,
            2,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 18, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "resort fee", "txTypeMontant" => 1, "txMontant" => 25, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 3, "txName" => "city tax", "txTypeMontant" => 1, "txMontant" => 5, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["zanzibar2"] = [
            'Zanzibar2',
            1500.00,
            1237.2881,
            1500.00,
            2,
            5,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 18, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "city tax", "txTypeMontant" => 1, "txMontant" => 4, "txFormule" => "BY_NIGHT_AND_PERSON_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        // -------------------- Exemple albanie --------------------
        $ex["albanie"] = [
            'Albanie',
            143.28,
            129.0811,
            143.28,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 6, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "city tax", "txTypeMontant" => 0, "txMontant" => 5, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        // -------------------- Exemple turkey --------------------
        $ex["turkey"] = [
            'Turkey',
            136.00,
            121.4286,
            136.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "governemnt tax", "txTypeMontant" => 0, "txMontant" => 2, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        // -------------------- Exemple chine --------------------
        $ex["chine1"] = [
            'Chine1',
            2651.0377,
            2285.3774,
            2422.50,
            2,
            3,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 6,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
                ["id" => 2, "txName" => "city tax", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
            ]
        ];

        $ex["chine2"] = [
            'Chine2',
            2775.84,
            2775.84,
            2775.84,
            4,
            3,
            []
        ];

        $ex["chine3"] = [
            'Chine3',
            517.00,
            487.7358,
            517.00,
            2,
            1,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 6, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => true],
            ]
        ];

        $ex["chine4"] = [
            'Chine4',
            2424.6037,
            2079.42,
            2079.42,
            4,
            3,
            [
                ["id" => 1, "txName" => "VAT", "txTypeMontant" => 0, "txMontant" => 6,  "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [], "txInc" => false],
                ["id" => 2, "txName" => "service charge", "txTypeMontant" => 0, "txMontant" => 10, "txFormule" => "BY_STAY_TAX", "taxe_cumul" => [1], "txInc" => false],
            ]
        ];



        return array_values($ex);
    }
}
