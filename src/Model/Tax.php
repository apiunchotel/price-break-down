<?php

namespace Hi\Model;

/**
 * Model Tax
 *
 * @author Essahel Adil <adil.essahel@uncubus.com> 
 */
class Tax
{

    /**
     * Price HT
     * @var float
     */
    private $priceHT;

    /**
     * Price Sale
     * @var float
     */
    private $priceSale;

    /**
     * Price TTC
     * @var float
     */
    private $priceTTC;

    /**
     * Total price excluded
     * @var float
     */
    private $totalTaxExc;

    /**
     * Total price Included
     * @var float
     */
    private $totalTaxInc;

    /**
     * Details price
     * @var array
     */
    private $detailTax;

    /**
     * Details taxes used
     * @var array
     */
    private $originalTaxes;

    public function getPriceHT(): float
    {
        return $this->priceHT;
    }

    public function getPriceSale(): float
    {
        return $this->priceSale;
    }

    public function getPriceTTC(): float
    {
        return $this->priceTTC;
    }

    public function getTotalTaxExc(): float
    {
        return $this->totalTaxExc;
    }

    public function getTotalTaxInc(): float
    {
        return $this->totalTaxInc;
    }

    public function getDetailTax(): array
    {
        return $this->detailTax;
    }

    public function getOriginalTaxes(): array
    {
        return $this->originalTaxes;
    }

    public function setPriceHT(float $priceHT)
    {
        $this->priceHT = $priceHT;
        return $this;
    }

    public function setPriceSale(float $priceSale): self
    {
        $this->priceSale = $priceSale;
        return $this;
    }

    public function setPriceTTC(float $priceTTC): self
    {
        $this->priceTTC = $priceTTC;
        return $this;
    }

    public function setTotalTaxExc(float $totalTaxExc): self
    {
        $this->totalTaxExc = $totalTaxExc;
        return $this;
    }

    public function setTotalTaxInc(float $totalTaxInc): self
    {
        $this->totalTaxInc = $totalTaxInc;
        return $this;
    }

    public function setDetailTax(array $detailTax): self
    {
        $this->detailTax = $detailTax;
        return $this;
    }

    public function setOriginalTaxes(array $originalTaxes): self
    {
        $this->originalTaxes = $originalTaxes;
        return $this;
    }

    public function toArray()
    {
        return [
            'priceTTC' => $this->priceTTC,
            'priceHT' => $this->priceHT,
            'priceSale' => $this->priceSale,
            'totalTaxExc' => $this->totalTaxExc,
            'totalTaxInc' => $this->totalTaxInc,
            'detailTax' => $this->detailTax,
            'originalTaxes' => $this->originalTaxes,
        ];
    }

}
