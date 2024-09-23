<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ExportExcelTransactions;
use Illuminate\Support\Facades\Storage;

class ExcelController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/download-excel",
     *     summary="Descargar Excel con transacciones",
     *     description="Permite descargar un archivo Excel con los datos de transacciones proporcionados. El archivo es almacenado temporalmente en S3 y se devuelve una URL temporal para su descarga.",
     *     operationId="downloadExcel",
     *     tags={"Export"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Datos de las transacciones a exportar",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(
     *                 property="headings",
     *                 type="array",
     *                 description="Encabezados para las columnas del archivo Excel",
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Archivo generado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="path", type="string", description="URL temporal para descargar el archivo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la validación de la solicitud",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="The data field is required."),
     *             @OA\Property(property="message", type="string", description="Mensaje adicional del error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error en la generación del archivo",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Download Exception failed"),
     *             @OA\Property(property="message", type="string", description="Mensaje de la excepción")
     *         )
     *     )
     * )
     */
    public function downloadExcel(Request $request)
    {
        $this->validate($request, [
            'data' => 'required|array',
            'headings' => 'required|array',
        ]);
        
        try {
            $data = $request->data;
            $headings = $request->headings;

            $export = new ExportExcelTransactions($data, $headings);
            $fileName = 'transactions-' . time() . '.csv';
            $path = 'exports/' . $fileName;
            $export->store($path, 's3');

            $expiration = now()->addMinutes(60);
            $url = Storage::temporaryUrl($path, $expiration);

            return response()->json(['path' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Download Exception failed', 'message' => $e->getMessage()]);
        }
    }
}
