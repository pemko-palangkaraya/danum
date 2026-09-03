<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\LetterVariableSchema;
use PHPUnit\Framework\TestCase;

class LetterVariableSchemaTest extends TestCase
{
    public function test_it_parses_repeater_definition(): void
    {
        $schema = LetterVariableSchema::parseRepeater('@repeat pelaksana|Nama:nama,NIP:nip,Jabatan:jabatan');

        $this->assertSame('pelaksana', $schema['key']);
        $this->assertSame(['key' => 'nama', 'label' => 'Nama'], $schema['fields'][0]);
        $this->assertSame(['key' => 'nip', 'label' => 'NIP'], $schema['fields'][1]);
        $this->assertSame(['key' => 'jabatan', 'label' => 'Jabatan'], $schema['fields'][2]);
    }

    public function test_invalid_repeater_definition_is_rejected(): void
    {
        $this->assertNull(LetterVariableSchema::parseRepeater('@repeat pelaksana'));
        $this->assertNull(LetterVariableSchema::parseRepeater('@repeat pelaksana|Nama:nama,invalid-field'));
    }
}
