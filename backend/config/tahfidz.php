<?php

return [

    /*
    | Kontak developer untuk menu Bantuan. Sengaja di-hardcode (bukan data pengguna);
    | env hanya untuk override di server tertentu. Nomor disimpan dalam format lokal 08xx.
    */
    'developer' => [
        'nama' => 'Developer',
        'telp' => env('DEVELOPER_TELP', '082142986689'),
    ],

];
