<?php

namespace App\Services;

use App\Models\FacadeRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class FacadeRequestPdfService
{
    /**
     * Genera el PDF de una solicitud (datos + fotos comprimidas + firmas,
     * todo incrustado como base64 para no depender de que dompdf pueda
     * leer el disco privado), lo guarda en storage y devuelve la ruta.
     */
    public function generate(FacadeRequest $facadeRequest, string $statusLabel): string
    {
        $media = $this->preparePhotosAndSignatures($facadeRequest);

        $pdf = Pdf::loadView('facades.pdf', [
            'facadeRequest' => $facadeRequest,
            'statusLabel' => $statusLabel,
            'photos' => $media['photos'],
            'clientSignatureDataUri' => $media['clientSignatureDataUri'],
            'agentSignatureDataUri' => $media['agentSignatureDataUri'],
        ]);

        $path = "facade-requests/{$facadeRequest->id}/pdf/{$facadeRequest->folio}.pdf";

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Prepara fotos (comprimidas) y firmas como data URIs base64, para
     * incrustar tanto en el PDF como en la pantalla pública de
     * autorización, sin depender de rutas de archivo ni de sesión.
     *
     * @return array{photos: array<int, array{data_uri: string, original_name: string}>, clientSignatureDataUri: ?string, agentSignatureDataUri: ?string}
     */
    public function preparePhotosAndSignatures(FacadeRequest $facadeRequest): array
    {
        $facadeRequest->loadMissing('photos');

        $photos = [];

        foreach ($facadeRequest->photos as $photo) {
            if (! Storage::disk('local')->exists($photo->path)) {
                continue;
            }

            $dataUri = $this->compressPhotoForPdf(Storage::disk('local')->get($photo->path));

            if ($dataUri !== null) {
                $photos[] = [
                    'data_uri' => $dataUri,
                    'original_name' => $photo->original_name ?? basename($photo->path),
                ];
            }
        }

        return [
            'photos' => $photos,
            'clientSignatureDataUri' => $this->signatureDataUri($facadeRequest->client_signature_path),
            'agentSignatureDataUri' => $this->signatureDataUri($facadeRequest->agent_signature_path),
        ];
    }

    /**
     * Reduce la foto a un ancho máximo de ~800px y la recomprime como
     * JPEG calidad ~70% solo para el PDF; la foto original en storage no
     * se modifica. Devuelve null si el binario no es una imagen válida.
     */
    private function compressPhotoForPdf(string $binary): ?string
    {
        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return null;
        }

        $maxWidth = 800;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round($height * ($maxWidth / $width));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagejpeg($image, null, 70);
        $jpegData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/jpeg;base64,'.base64_encode($jpegData);
    }

    private function signatureDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
