<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\File;

class ImportarImagesController extends Controller
{
    public function importImagenes(Request $request)
    {
        try {
            if ($request->hasFile('file')) {
                $archivo = $request->file('file');
                $nombreArchivo = $archivo->getClientOriginalName();
                $directorioDestino = public_path('imagenes_personas');

                $archivo->move($directorioDestino, $nombreArchivo);

                $rutaArchivo = $directorioDestino . '/' . $nombreArchivo;
                if (pathinfo($rutaArchivo, PATHINFO_EXTENSION) === 'zip') {
                    $zip = new ZipArchive;
                    if ($zip->open($rutaArchivo) === true) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $nombreArchivo = $zip->getNameIndex($i);
                            $partesNombre = pathinfo($nombreArchivo);
                            $ci_persona = $partesNombre['filename'];
                            $extension = $partesNombre['extension'];
                            $persona = Persona::where('ci_persona', $ci_persona)->first();
                            if ($persona) {
                                $nombreImagen = $ci_persona . '.' . $extension;
                                $zip->extractTo(Storage::disk('img_personas')->path('/'), $nombreArchivo);
                                $persona->imagen = $nombreImagen;
                                $persona->save();
                            }
                        }
                        $zip->close();
                    }
                    unlink($rutaArchivo);
                    return $this->sendSuccess(['msn' => 'Imágenes importadas correctamente.']);
                } else {
                    return $this->sendError('El archivo no es un archivo ZIP.');
                }
            } else {
                return $this->sendError('No se ha proporci_personaonado un archivo.');
            }
            return $this->sendSuccess(['msn' => 'Imágenes importadas correctamente.']);
        } catch (\Exception $e) {
            return $this->sendError('Error: ' . $e->getMessage());
        }
    }

    public function getImagenPersona($personaId)
    {
        $persona = Persona::where('id', $personaId)->first();
        if (isset($persona)) {
            $disk = Storage::disk('img_personas');
            $content = $disk->get($persona->imagen);
            $mime = File::mimeType($disk->path($persona->imagen));
            return response($content)->header('Content-Type', $mime);
        } else {
            return $this->sendError(['msn'=>'No se encontro a la persona'],404);
        }
    }
}
