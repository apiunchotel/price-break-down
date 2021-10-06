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
//        dump($result);
        $this->assertEquals($result["ttc"], $valueTTC, "TTC: Failed asserting that {$valueTTC} matches expected {$result["ttc"]}.");
        $this->assertEquals($result["ht"], $priceHT, "HT: Failed asserting that {$priceHT} matches expected {$result["ht"]}.");
        $this->assertEquals($result["sale"], $valueSale, "Sale: Failed asserting that {$valueSale} matches expected {$result["sale"]}.");
        $this->assertEquals($result["totalExc"], $totalExc, "TotalExc: Failed asserting that {$totalExc} matches expected {$result["totalExc"]}.");
        $this->assertEquals($result["totalInc"], $totalInc, "TotalInc: Failed asserting that {$totalInc} matches expected {$result["totalInc"]}.");
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
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays, 'priceTTC' => 107, 'priceSale' => 107, 'totalExc' => 0, 'totalInc' => 7,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_STAY_TAX,
                        "txInc" => true,
                        "txName" => "tva",
                        "txMontant" => 7,
                        "txTypeMontant" => TaxeDetail::MONTANT_FIX_TAX,
                        "txOta" => 1,
                    ]
                ]
            ],
            "TaxeDetail::BY_STAY_TAX/MONTANT_FIX_TAX/EXCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays, 'priceTTC' => 107, 'priceSale' => 100, 'totalExc' => 7, 'totalInc' => 0,
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
            "TaxeDetail::BY_NIGHT_TAX/MONTANT_PERCENT_TAX/INCLUDED" => [
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 10 * 2, 'priceSale' => $priceHT + 10 * 2, 'totalExc' => 0, 'totalInc' => 10 * 2,
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
                'priceHT' => $priceHT, 'nbPerson' => $nbPerson, 'nbDays' => $nbDays * 2, 'priceTTC' => $priceHT + 10 * 2, 'priceSale' => $priceHT, 'totalExc' => 10 * 2, 'totalInc' => 0,
                [
                    'taxes' => ["txFormule" => TaxeDetail::BY_NIGHT_TAX,
                        "txInc" => false,
                        "txName" => "tva",
                        "txMontant" => 10,
                        "txTypeMontant" => TaxeDetail::MONTANT_PERCENT_TAX,
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
        ];
    }

//    public function testTaxeFromTTC(): void
//    {
//        
//    }
//
//    public function testTaxeFromSale(): void
//    {
//        
//    }
}
