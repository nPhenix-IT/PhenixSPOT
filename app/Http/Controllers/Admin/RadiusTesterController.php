<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RadiusTesterController extends Controller
{
    /**
     * Affiche la page du testeur RADIUS.
     */
    public function index()
    {
        return view('content.admin.radius_tester.index');
    }

    /**
     * Exécute le test radtest.
     */
    public function test(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'address' => 'required|ipv4',
            'port' => 'required|integer',
            'secret' => 'required|string',
        ]);

        // CORRECTION : On passe les arguments directement au constructeur Process,
        // qui les échappe de manière sécurisée sans ajouter de guillemets superflus.
        $command = [
            'radtest',
            $data['username'],
            $data['password'],
            $data['address'],
            $data['port'],
            $data['secret'],
        ];

        $process = new Process($command);

        try {
            // On utilise run() au lieu de mustRun() pour capturer la sortie même en cas d'échec.
            $process->run();
            $output = $process->getOutput() . $process->getErrorOutput();
        } catch (ProcessFailedException $exception) {
            $output = $exception->getMessage();
        }

        return response()->json(['output' => $output]);
    }
}
