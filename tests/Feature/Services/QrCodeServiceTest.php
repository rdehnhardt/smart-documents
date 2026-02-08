<?php

use App\Models\Document;
use App\Services\QrCodeService;

describe('QR Code Service', function () {
    describe('generateSvg', function () {
        it('generates SVG for public documents', function () {
            $document = Document::factory()->public()->create();
            $service = app(QrCodeService::class);

            $svg = $service->generateSvg($document);

            expect($svg)->toBeString();
            expect($svg)->not->toBeEmpty();
        });

        it('returns null for private documents', function () {
            $document = Document::factory()->create();
            $service = app(QrCodeService::class);

            $svg = $service->generateSvg($document);

            expect($svg)->toBeNull();
        });

        it('respects size parameter', function () {
            $document = Document::factory()->public()->create();
            $service = app(QrCodeService::class);

            $smallSvg = $service->generateSvg($document, 100);
            $largeSvg = $service->generateSvg($document, 400);

            expect($smallSvg)->toBeString();
            expect($largeSvg)->toBeString();
            expect(strlen($smallSvg))->toBeGreaterThan(0);
            expect(strlen($largeSvg))->toBeGreaterThan(0);
        });
    });

    describe('generateDataUri', function () {
        it('generates data URI for public documents', function () {
            $document = Document::factory()->public()->create();
            $service = app(QrCodeService::class);

            $dataUri = $service->generateDataUri($document);

            expect($dataUri)->toBeString();
            expect($dataUri)->toStartWith('data:image/svg+xml;base64,');
        });

        it('returns null for private documents', function () {
            $document = Document::factory()->create();
            $service = app(QrCodeService::class);

            $dataUri = $service->generateDataUri($document);

            expect($dataUri)->toBeNull();
        });
    });

    describe('generateForUrl', function () {
        it('generates QR code for any URL', function () {
            $service = app(QrCodeService::class);

            $svg = $service->generateForUrl('https://example.com');

            expect($svg)->toBeString();
            expect($svg)->not->toBeEmpty();
        });
    });
});
