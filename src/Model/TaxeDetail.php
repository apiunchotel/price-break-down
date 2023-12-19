<?php

namespace Apiunchotel\PriceBreakDown\Model;

/**
 * Model TaxeDetail
 *
 * @author Essahel Adil <adil.essahel@uncubus.com> 
 */
class TaxeDetail
{

    /**
     * Montant fix:
     */
    CONST MONTANT_FIX_TAX = 1;

    /**
     * Montant pourcentage:
     */
    CONST MONTANT_PERCENT_TAX = 0;

    /**
     * Sejour:
     */
    CONST BY_STAY_TAX = 1;

    /**
     * Nuit
     */
    CONST BY_NIGHT_TAX = 2;

    /**
     * Nuit & Personne
     */
    CONST BY_NIGHT_AND_PERSON_TAX = 3;

    /**
     * Personne
     */
    CONST BY_PERSON_TAX = 4;

    protected $txFormule;
    protected $txInc;
    protected $txName;
    protected $txMontant;
    protected $txTypeMontant;
    protected $txOta;

    public function getTxFormule(): int
    {
        return (int) $this->txFormule;
    }

    public function getTxInc(): bool
    {
        return (bool) - $this->txInc;
    }

    public function getTxName(): string
    {
        return (string) $this->txName;
    }

    public function getTxMontant(): float
    {
        return (float) $this->txMontant;
    }

    public function getTxTypeMontant(): int
    {
        return (int) $this->txTypeMontant;
    }

    public function getTxOta(): ?int
    {
        return $this->txOta;
    }

    public function setTxFormule(int $txFormule): self
    {
        $this->txFormule = $txFormule;
        return $this;
    }

    public function setTxInc(bool $txInc): self
    {
        $this->txInc = $txInc;
        return $this;
    }

    public function setTxName(string $txName): self
    {
        $this->txName = $txName;
        return $this;
    }

    public function setTxMontant(float $txMontant): self
    {
        $this->txMontant = $txMontant;
        return $this;
    }

    public function setTxTypeMontant(int $txTypeMontant): self
    {
        $this->txTypeMontant = $txTypeMontant;
        return $this;
    }

    public function setTxOta(?int $txOta): self
    {
        $this->txOta = $txOta;
        return $this;
    }

    /**
     * Type de montant est fix
     * 
     * @return bool
     */
    public function montantIsFix(): bool
    {
        return $this->getTxTypeMontant() == self::MONTANT_FIX_TAX;
    }

    public function toArray()
    {
        return [
            'txName' => $this->getTxName(),
            'txMontant' => $this->getTxMontant(),
            'txFormule' => $this->getTxFormule(),
            'txTypeMontant' => $this->getTxTypeMontant(),
            'txInc' => $this->getTxInc()
        ];
    }

}
