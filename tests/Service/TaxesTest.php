<?php

namespace App\Tests\Service;

use App\Service\TaxesService;
use PHPUnit\Framework\TestCase;
use App\Model\TaxeDetail;

/**
 * Service Taxes Hi
 *
 * @author Essahel Adil <adil.essahel@uncubus.com> 
 */
class TaxesTest extends TestCase
{

    public function testConvertToObject(): void
    {
        $tx = new TaxesService();
        $taxes = ["txFormule" => TaxeDetail::BY_STAY_TAX,
            "txInc" => true,
            "txName" => "tva",
            "txMontant" => 10,
            "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
            "txOta" => 1,
        ];
        $result = $tx->convertToObject($taxes);
        $this->assertInstanceOf(TaxeDetail::class, $result);
    }

    /**
     * @dataProvider taxesPriceHtProvider
     */
    public function testTaxeFromHt($priceHT, $nbPerson, $nbDays, $valueTTC, $valueSale, $totalExc, $totalInc, $taxes): void
    {
        $tx = new TaxesService();
        $result = $tx->getTaxeFromHt($priceHT, $taxes, $nbPerson, $nbDays);
        $this->assertEquals($result["priceTTC"], $valueTTC, "TTC: Failed asserting that {$valueTTC} matches expected {$result["priceTTC"]}.");
        $this->assertEquals($result["priceHT"], $priceHT, "HT: Failed asserting that {$priceHT} matches expected {$result["priceHT"]}.");
        $this->assertEquals($result["priceSale"], $valueSale, "Sale: Failed asserting that {$valueSale} matches expected {$result["priceSale"]}.");
        $this->assertEquals($result["totalTaxExc"], $totalExc, "TotalExc: Failed asserting that {$totalExc} matches expected {$result["totalTaxExc"]}.");
        $this->assertEquals($result["totalTaxInc"], $totalInc, "TotalInc: Failed asserting that {$totalInc} matches expected {$result["totalTaxInc"]}.");
    }

    /**
     * @dataProvider taxesPriceHtProvider
     */
    public function testPriceHTFromSale($priceHT, $nbPerson, $nbDays, $valueTTC, $valueSale, $totalExc, $totalInc, $taxes): void
    {
        $tx = new TaxesService();
        $result = $tx->getPriceHTFromSale($valueSale, $taxes, $nbPerson, $nbDays);
        $this->assertEquals($result, $priceHT, "HT: Failed asserting that {$priceHT} matches expected {$result}.");
    }
    
    /**
     * @dataProvider taxesPriceHtProvider
     */
    public function testTaxeFromSale($priceHT, $nbPerson, $nbDays, $valueTTC, $valueSale, $totalExc, $totalInc, $taxes): void
    {
        $tx = new TaxesService();
        $result = $tx->getTaxeFromSale($valueSale, $taxes, $nbPerson, $nbDays);
        $this->assertEquals($result["priceTTC"], $valueTTC, "TTC: Failed asserting that {$valueTTC} matches expected {$result["priceTTC"]}.");
        $this->assertEquals($result["priceHT"], $priceHT, "HT: Failed asserting that {$priceHT} matches expected {$result["priceHT"]}.");
        $this->assertEquals($result["priceSale"], $valueSale, "Sale: Failed asserting that {$valueSale} matches expected {$result["priceSale"]}.");
        $this->assertEquals($result["totalTaxExc"], $totalExc, "TotalExc: Failed asserting that {$totalExc} matches expected {$result["totalTaxExc"]}.");
        $this->assertEquals($result["totalTaxInc"], $totalInc, "TotalInc: Failed asserting that {$totalInc} matches expected {$result["totalTaxInc"]}.");
    }


    public function taxesPriceHtProvider(): array
    {
        $priceHT = 100;
        $nbPerson = 1;
        $nbDays = 1;

        return [
            "TaxeDetail::BY_STAY_TAX/MONTANT_PERCENT_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays, 'priceTTC' => 110, 'priceSale' => 110, 'totalExc' => 0, 'totalInc' => 10,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_STAY_TAX/MONTANT_FIX_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays, 'priceTTC' => $priceHT + 10, 'priceSale' => $priceHT + 10, 'totalExc' => 0, 'totalInc' => 10,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_STAY_TAX/MONTANT_FIX_TAX/EXCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays, 'priceTTC' => $priceHT + 7, 'priceSale' => $priceHT, 'totalExc' => 7, 'totalInc' => 0,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => false,
                        "txName" => "tva",
                        "txMontant" => 7,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_STAY_TAX/GENERAL" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays, 'priceTTC' => $priceHT + ($priceHT * ((10 + 5 + 5) / 100)) + 7 + 8 + 9, 'priceSale' => $priceHT + ($priceHT * ((10 + 5) / 100)) + 8 + 9, 'totalExc' => 7 + ($priceHT * (5 / 100)), 'totalInc' => ($priceHT * ((10 + 5) / 100)) + 8 + 9,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => true,
                        "txName" => "tva1",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes00' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => false,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes0' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => true,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes1' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => false,
                        "txName" => "tva3",
                        "txMontant" => 7,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes2' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => true,
                        "txName" => "tva4",
                        "txMontant" => 8,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes3' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => true,
                        "txName" => "tva5",
                        "txMontant" => 9,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                ]
            ],
            "TaxeDetail::BY_NIGHT_TAX/MONTANT_PERCENT_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + ($priceHT * (10 * 2 / 100)), 'priceSale' => $priceHT + ($priceHT * (10 * 2 / 100)), 'totalExc' => 0, 'totalInc' => $priceHT * (10 * 2 / 100),
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_TAX/MONTANT_PERCENT_TAX/EXCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + ($priceHT * (10 * 2 / 100)) + 5 * 2, 'priceSale' => $priceHT + ($priceHT * (10 * 2 / 100)), 'totalExc' => 5 * 2, 'totalInc' => ($priceHT * (10 * 2 / 100)),
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes2' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => false,
                        "txName" => "tva1",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_TAX/MONTANT_FIX_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 6 * 2, 'priceSale' => $priceHT + 6 * 2, 'totalExc' => 0, 'totalInc' => 6 * 2,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 6,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_TAX/MONTANT_FIX_TAX/EXCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 6 * 2, 'priceSale' => $priceHT, 'totalExc' => 6 * 2, 'totalInc' => 0,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => false,
                        "txName" => "tva",
                        "txMontant" => 6,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_TAX/GENERAL" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + ($priceHT * ((10 + 5 + 5) * 2 / 100)) + 7 * 2 + 8 * 2 + 9 * 2, 'priceSale' => $priceHT + ($priceHT * ((10 + 5) * 2 / 100)) + 8 * 2 + 9 * 2, 'totalExc' => 7 * 2 + ($priceHT * (5 * 2 / 100)), 'totalInc' => ($priceHT * ((10 + 5) * 2 / 100)) + 8 * 2 + 9 * 2,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => true,
                        "txName" => "tva1",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes00' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => false,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes0' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => true,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes1' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => false,
                        "txName" => "tva3",
                        "txMontant" => 7,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes2' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => true,
                        "txName" => "tva4",
                        "txMontant" => 8,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes3' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => true,
                        "txName" => "tva5",
                        "txMontant" => 9,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                ]
            ],
            "TaxeDetail::BY_NIGHT_AND_PERSON_TAX/MONTANT_PERCENT_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 3, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 10 * 2 * 3, 'priceSale' => $priceHT + 10 * 2 * 3, 'totalExc' => 0, 'totalInc' => 10 * 2 * 3,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_AND_PERSON_TAX/MONTANT_PERCENT_TAX/EXCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 3, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 10 * 2 * 3, 'priceSale' => $priceHT, 'totalExc' => 10 * 2 * 3, 'totalInc' => 0,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => false,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_AND_PERSON_TAX/MONTANT_FIX_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 3, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 9 * 2 * 3, 'priceSale' => $priceHT + 9 * 2 * 3, 'totalExc' => 0, 'totalInc' => 9 * 2 * 3,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 9,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_AND_PERSON_TAX/MONTANT_FIX_TAX/EXCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 3, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 9 * 2 * 3, 'priceSale' => $priceHT, 'totalExc' => 9 * 2 * 3, 'totalInc' => 0,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => false,
                        "txName" => "tva",
                        "txMontant" => 9,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_NIGHT_AND_PERSON_TAX/GENERAL" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 2, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + ($priceHT * ((10 + 5 + 5) * 2 * 2 / 100)) + 7 * 2 * 2 + 8 * 2 * 2 + 9 * 2 * 2, 'priceSale' => $priceHT + ($priceHT * ((10 + 5) * 2 * 2 / 100)) + 8 * 2 * 2 + 9 * 2 * 2, 'totalExc' => 7 * 2 * 2 + ($priceHT * (5 * 2 * 2 / 100)), 'totalInc' => ($priceHT * ((10 + 5) * 2 * 2 / 100)) + 8 * 2 * 2 + 9 * 2 * 2,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva1",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes00' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => false,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes0' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes1' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => false,
                        "txName" => "tva3",
                        "txMontant" => 7,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes2' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva4",
                        "txMontant" => 8,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes3' => ["txFormule" => TaxeDetail::BY_NIGHT_AND_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva5",
                        "txMontant" => 9,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                ]
            ],
            "TaxeDetail::BY_PERSON_TAX/MONTANT_PERCENT_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 5, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 10 * 5, 'priceSale' => $priceHT + 10 * 5, 'totalExc' => 0, 'totalInc' => 10 * 5,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_PERSON_TAX/MONTANT_PERCENT_TAX/EXCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 5, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 10 * 5, 'priceSale' => $priceHT, 'totalExc' => 10 * 5, 'totalInc' => 0,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => false,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_PERSON_TAX/MONTANT_FIX_TAX" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 4, 'nbDays' => $nbDays * 3, 'priceTTC' => $priceHT + 9 * 4, 'priceSale' => $priceHT + 9 * 4, 'totalExc' => 0, 'totalInc' => 9 * 4,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 9,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_PERSON_TAX/GENERAL" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson * 2, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + ($priceHT * ((10 + 5 + 5) * 2 / 100)) + 7 * 2 + 8 * 2 + 9 * 2, 'priceSale' => $priceHT + ($priceHT * ((10 + 5) * 2 / 100)) + 8 * 2 + 9 * 2, 'totalExc' => 7 * 2 + ($priceHT * (5 * 2 / 100)), 'totalInc' => ($priceHT * ((10 + 5) * 2 / 100)) + 8 * 2 + 9 * 2,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva1",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes00' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => false,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes0' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva2",
                        "txMontant" => 5,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
                        "txOta" => 1,
                    ],
                    'taxes1' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => false,
                        "txName" => "tva3",
                        "txMontant" => 7,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes2' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva4",
                        "txMontant" => 8,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                    'taxes3' => ["txFormule" => TaxeDetail::BY_PERSON_TAX,
                        "txInc" => true,
                        "txName" => "tva5",
                        "txMontant" => 9,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ],
                ]
            ],
        ];
    }

//    public function testTaxeFromTTC(): void
//    {
//        
//    }
//
}
