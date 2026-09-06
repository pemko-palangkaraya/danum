<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\PositionHolder;
use Illuminate\Support\Carbon;

final class LetterVariableSourceResolver
{
    public function __construct(private readonly LetterVariableDateService $date) {}

    public function system(?PositionHolder $holder = null): array
    {
        $tenant = $holder?->user?->tenant ?? auth()->user()?->tenant;
        if (! $tenant) return [];
        $name = (string) ($holder?->user?->name ?? $tenant->head_name ?? '');
        $title = (string) ($holder?->position?->name ?? $tenant->head_title ?? '');
        return ['tenant_name'=>$tenant->name,'tenant_city'=>$tenant->city,'tenant_district'=>$tenant->district,'tenant_village'=>$tenant->village,'tenant_province'=>$tenant->province,'tenant_address'=>$tenant->address,'tenant_phone'=>$tenant->phone,'tenant_email'=>$tenant->email,'tenant_head_name'=>$name,'tenant_head_title'=>$title,'nama_ttd'=>$name,'jabatan_ttd'=>$title];
    }

    public function citizen(Citizen $citizen): array
    {
        return ['citizen_nik'=>$citizen->nik,'citizen_nama_lengkap'=>$citizen->nama_lengkap,'citizen_tempat_lahir'=>$citizen->tempat_lahir,'citizen_tanggal_lahir'=>$this->date->format($citizen->tanggal_lahir),'citizen_jenis_kelamin'=>$this->gender($citizen->jenis_kelamin),'citizen_golongan_darah'=>$citizen->golongan_darah,'citizen_agama'=>$citizen->agama,'citizen_status_perkawinan'=>$citizen->status_perkawinan,'citizen_pendidikan'=>$citizen->pendidikan,'citizen_pekerjaan'=>$citizen->pekerjaan,'citizen_kewarganegaraan'=>$citizen->kewarganegaraan,'citizen_no_passport'=>$citizen->no_passport,'citizen_no_kitap'=>$citizen->no_kitap,'citizen_nama_ayah'=>$citizen->nama_ayah,'citizen_nik_ayah'=>$citizen->nik_ayah,'citizen_nama_ibu'=>$citizen->nama_ibu,'citizen_nik_ibu'=>$citizen->nik_ibu,'citizen_status_kependudukan'=>$citizen->status_kependudukan,'recipient_name'=>$citizen->nama_lengkap,'recipient_nik'=>$citizen->nik,'recipient_gender'=>$this->gender($citizen->jenis_kelamin),'recipient_birth_place'=>$citizen->tempat_lahir,'recipient_birth_date'=>$this->date->format($citizen->tanggal_lahir),'recipient_religion'=>$citizen->agama,'recipient_occupation'=>$citizen->pekerjaan];
    }

    public function family(Citizen $citizen): array
    {
        $family = $this->familyFor($citizen);
        if (! $family) return ['values'=>['recipient_address'=>'','nama_pasangan'=>'-'],'children'=>[['nomor'=>'1','nama'=>'-']]];
        $members = $family->activeMembers->filter(fn($member)=>(string)$member->citizen_id !== (string)$citizen->id)->values();
        $spouse = $members->first(fn($member)=>in_array(mb_strtolower(trim((string)$member->hubungan_dalam_keluarga)),['suami','istri','pasangan'],true));
        $children = $members->filter(fn($member)=>mb_strtolower(trim((string)$member->hubungan_dalam_keluarga))==='anak')->values()->map(fn($member,int $index)=>['nomor'=>(string)($index+1),'nama'=>(string)($member->citizen?->nama_lengkap ?: '-')])->all();
        return ['values'=>['recipient_address'=>$this->address($family),'nama_pasangan'=>(string)($spouse?->citizen?->nama_lengkap ?: '-')],'children'=>$children ?: [['nomor'=>'1','nama'=>'-']]];
    }

    public function age(Citizen $citizen, mixed $deathDate): string
    {
        $normalized = $this->date->normalize($deathDate);
        if (!$citizen->tanggal_lahir || !$normalized) return '';
        try {
            $age = Carbon::parse($citizen->tanggal_lahir)->diffInYears(Carbon::parse($normalized), false);
            return $age >= 0 ? (string) floor((float)$age) : '';
        } catch (\Throwable) { return ''; }
    }

    private function familyFor(Citizen $citizen): ?Family
    {
        $membership = $citizen->activeFamilyMembership()->with('family.activeMembers.citizen')->first();
        if ($membership?->family) return $membership->family;
        return $citizen->headedFamilies()->where('status','active')->with('activeMembers.citizen')->first();
    }

    private function address(Family $family): string
    {
        return collect([$family->alamat,filled($family->rt)?'RT '.$family->rt:null,filled($family->rw)?'RW '.$family->rw:null,$family->kelurahan,$family->kecamatan,$family->kabupaten_kota,$family->provinsi,$family->kode_pos])->filter(fn($value)=>filled($value))->implode(', ');
    }

    private function gender(mixed $value): string
    {
        return match(mb_strtolower(trim((string)$value))){'male','laki-laki','laki laki','l'=>'Laki-laki','female','perempuan','p'=>'Perempuan',default=>(string)$value};
    }
}
