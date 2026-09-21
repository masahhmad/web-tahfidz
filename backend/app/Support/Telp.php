<?php

namespace App\Support;

/**
 * Nomor telepon disimpan dalam format lokal (08xxxxxxxxxx). wa.me butuh format
 * internasional tanpa "+" atau "0" di depan (628xxxxxxxxxx).
 */
class Telp
{
    /** 08 + 8–12 digit (total 10–14 digit). */
    public const PATTERN = '/^08[0-9]{8,12}$/';

    public const MESSAGE = 'Nomor telepon harus diawali 08 dan berisi 10-14 digit angka.';

    public static function toWhatsapp(string $telp): string
    {
        return '62'.substr($telp, 1);
    }

    public static function waUrl(?string $telp): ?string
    {
        return $telp ? 'https://wa.me/'.self::toWhatsapp($telp) : null;
    }
}
