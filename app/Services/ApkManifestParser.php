<?php

namespace App\Services;

/**
 * Reads versionCode/versionName directly out of an APK's compiled
 * AndroidManifest.xml (Android's binary XML / "AXML" format), so the
 * SuperAdmin app-update form can never drift from what's actually inside
 * the uploaded APK — see AppUpdateController, which previously let an admin
 * type any version_code by hand with zero connection to the real APK,
 * letting the server advertise a version the installed build could never
 * satisfy (endless "update available" loop even after installing "latest").
 */
class ApkManifestParser
{
    private const CHUNK_STRING_POOL = 0x0001;
    private const CHUNK_XML_START_ELEMENT = 0x0102;
    private const UTF8_FLAG = 0x00000100;
    private const TYPE_STRING = 0x03;

    /**
     * @return array{version_code:int|null, version_name:string|null, package:string|null}
     */
    public static function parse(string $apkPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($apkPath) !== true) {
            throw new \RuntimeException('APK ফাইল খোলা যায়নি (zip নয়)।');
        }
        $manifest = $zip->getFromName('AndroidManifest.xml');
        $zip->close();

        if ($manifest === false) {
            throw new \RuntimeException('APK-এর ভেতরে AndroidManifest.xml পাওয়া যায়নি।');
        }

        return self::parseAxml($manifest);
    }

    private static function parseAxml(string $data): array
    {
        $len = strlen($data);
        $pos = 8; // skip root ResChunk_header (type u16, headerSize u16, size u32)

        $strings = [];
        $versionCode = null;
        $versionName = null;
        $package = null;

        while ($pos + 8 <= $len) {
            $chunkStart = $pos;
            $chunkType = self::u16($data, $pos);
            $pos += 2;
            $pos += 2; // headerSize, unused here
            $chunkSize = self::u32($data, $pos);
            $pos += 4;

            if ($chunkSize <= 0 || $chunkStart + $chunkSize > $len) {
                break;
            }

            if ($chunkType === self::CHUNK_STRING_POOL) {
                $strings = self::readStringPool($data, $chunkStart);
                $pos = $chunkStart + $chunkSize;
                continue;
            }

            if ($chunkType === self::CHUNK_XML_START_ELEMENT) {
                $pos += 4; // lineNumber
                $pos += 4; // comment (ResStringPool_ref)

                $nameIdx = self::u32($data, $pos + 4); // skip ns(4), read name(4)
                $attributeStart = self::u16($data, $pos + 8);
                $attributeSize = self::u16($data, $pos + 10);
                $attributeCount = self::u16($data, $pos + 12);

                $elementName = self::stringAt($strings, $nameIdx);

                $attrsBase = $chunkStart + 8 + 8 + $attributeStart;
                // (chunkHeader=8) + (lineNumber+comment=8) + attributeStart offset

                if ($elementName === 'manifest') {
                    for ($i = 0; $i < $attributeCount; $i++) {
                        $attrPos = $attrsBase + ($i * $attributeSize);
                        if ($attrPos + 20 > $len) {
                            break;
                        }
                        $attrNameIdx = self::u32($data, $attrPos + 4);
                        $attrRawValueIdx = self::u32($data, $attrPos + 8);
                        $dataType = ord($data[$attrPos + 15]);
                        $attrData = self::u32($data, $attrPos + 16);

                        $attrName = self::stringAt($strings, $attrNameIdx);

                        if ($attrName === 'versionCode') {
                            $versionCode = $attrData;
                        } elseif ($attrName === 'versionName') {
                            $versionName = $dataType === self::TYPE_STRING
                                ? self::stringAt($strings, $attrData)
                                : self::stringAt($strings, $attrRawValueIdx);
                        } elseif ($attrName === 'package') {
                            $package = self::stringAt($strings, $attrRawValueIdx) ?? self::stringAt($strings, $attrData);
                        }
                    }
                    break; // manifest tag found and read; nothing else needed
                }
            }

            $pos = $chunkStart + $chunkSize;
        }

        return [
            'version_code' => $versionCode !== null ? (int) $versionCode : null,
            'version_name' => $versionName,
            'package' => $package,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function readStringPool(string $data, int $chunkStart): array
    {
        $len = strlen($data);
        $p = $chunkStart + 8; // past ResChunk_header

        $stringCount = self::u32($data, $p);
        $styleCount = self::u32($data, $p + 4);
        $flags = self::u32($data, $p + 8);
        $stringsStart = self::u32($data, $p + 12);
        $isUtf8 = ($flags & self::UTF8_FLAG) !== 0;

        $offsetsBase = $p + 20; // header(8) + stringCount+styleCount+flags+stringsStart+stylesStart (5 x u32 = 20)
        $stringDataBase = $chunkStart + $stringsStart;

        $strings = [];
        for ($i = 0; $i < $stringCount; $i++) {
            $off = self::u32($data, $offsetsBase + ($i * 4));
            $strOffset = $stringDataBase + $off;
            if ($strOffset >= $len) {
                $strings[] = '';
                continue;
            }
            if ($isUtf8) {
                [, $consumed1] = self::readUtf8Varint($data, $strOffset); // utf16 char-count, unused
                $p2 = $strOffset + $consumed1;
                [$byteLen, $consumed2] = self::readUtf8Varint($data, $p2);
                $p3 = $p2 + $consumed2;
                $strings[] = substr($data, $p3, $byteLen);
            } else {
                [$charLen, $units] = self::readUtf16Varint($data, $strOffset);
                $p2 = $strOffset + ($units * 2);
                $bytes = substr($data, $p2, $charLen * 2);
                $strings[] = mb_convert_encoding($bytes, 'UTF-8', 'UTF-16LE');
            }
        }

        return $strings;
    }

    private static function stringAt(array $strings, ?int $idx): ?string
    {
        if ($idx === null || $idx === 0xFFFFFFFF || $idx < 0 || ! isset($strings[$idx])) {
            return null;
        }

        return $strings[$idx];
    }

    private static function readUtf8Varint(string $data, int $pos): array
    {
        $b0 = ord($data[$pos]);
        if ($b0 & 0x80) {
            $b1 = ord($data[$pos + 1]);
            return [(($b0 & 0x7F) << 8) | $b1, 2];
        }

        return [$b0, 1];
    }

    private static function readUtf16Varint(string $data, int $pos): array
    {
        $w0 = self::u16($data, $pos);
        if ($w0 & 0x8000) {
            $w1 = self::u16($data, $pos + 2);
            return [(($w0 & 0x7FFF) << 16) | $w1, 2];
        }

        return [$w0, 1];
    }

    private static function u16(string $data, int $pos): int
    {
        return unpack('v', substr($data, $pos, 2))[1];
    }

    private static function u32(string $data, int $pos): int
    {
        return unpack('V', substr($data, $pos, 4))[1];
    }
}
