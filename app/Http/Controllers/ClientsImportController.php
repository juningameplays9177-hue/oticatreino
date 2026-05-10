<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPhone;
use App\Models\ClientEmail;
use App\Models\ClientRef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

class ClientsImportController extends Controller
{
    /**
     * Mostrar formulário de importação
     */
    public function show()
    {
        return view('clients.import');
    }

    /**
     * Processar importação
     */
    public function run(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:5120', // 5MB
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remover cabeçalho
            $header = array_shift($rows);
            $header = array_map('strtolower', array_map('trim', $header));

            // Validar quantidade de linhas
            if (count($rows) > 1000) {
                return redirect()->back()
                    ->with('error', 'O arquivo não pode ter mais de 1.000 linhas. Linhas encontradas: ' . count($rows));
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 porque removemos header e index começa em 0

                try {
                    // Converter array associativo baseado no header
                    $data = [];
                    foreach ($header as $colIndex => $colName) {
                        $data[$colName] = $row[$colIndex] ?? null;
                    }

                    // Normalizar dados
                    $cpfCnpj = !empty($data['cpf_cnpj']) ? Client::normalizeCpfCnpj($data['cpf_cnpj']) : null;
                    $externalCode = !empty($data['external_code']) ? trim($data['external_code']) : null;
                    $type = !empty($data['type']) ? strtoupper(trim($data['type'])) : 'PF';
                    
                    if (!in_array($type, ['PF', 'PJ'])) {
                        $type = 'PF';
                    }

                    // Validar se tem CPF/CNPJ ou código externo
                    if (empty($cpfCnpj) && empty($externalCode)) {
                        $skipped++;
                        $errors[] = "Linha {$rowNumber}: CPF/CNPJ ou Código Externo é obrigatório";
                        continue;
                    }

                    // Buscar cliente existente
                    $client = null;
                    if (!empty($cpfCnpj)) {
                        $client = Client::where('cpf_cnpj', $cpfCnpj)->first();
                    }
                    if (!$client && !empty($externalCode)) {
                        $client = Client::where('external_code', $externalCode)->first();
                    }

                    // Preparar dados do cliente
                    $clientData = [
                        'type' => $type,
                        'name' => !empty($data['name']) ? trim($data['name']) : null,
                        'nickname' => !empty($data['nickname']) ? trim($data['nickname']) : null,
                        'cpf_cnpj' => $cpfCnpj,
                        'external_code' => $externalCode,
                        'origin' => !empty($data['origin']) ? trim($data['origin']) : null,
                        'rg_ie' => !empty($data['rg_ie']) ? trim($data['rg_ie']) : null,
                        'birth_date' => !empty($data['birth_date']) ? $this->parseDate($data['birth_date']) : null,
                        'cep' => !empty($data['cep']) ? trim($data['cep']) : null,
                        'city' => !empty($data['city']) ? trim($data['city']) : null,
                        'district' => !empty($data['district']) ? trim($data['district']) : null,
                        'address' => !empty($data['address']) ? trim($data['address']) : null,
                        'number' => !empty($data['number']) ? trim($data['number']) : null,
                        'complement' => !empty($data['complement']) ? trim($data['complement']) : null,
                        'father_name' => !empty($data['father_name']) ? trim($data['father_name']) : null,
                        'mother_name' => !empty($data['mother_name']) ? trim($data['mother_name']) : null,
                        'guardian_name' => !empty($data['guardian_name']) ? trim($data['guardian_name']) : null,
                        'guardian_relation' => !empty($data['guardian_relation']) ? trim($data['guardian_relation']) : null,
                        'profession' => !empty($data['profession']) ? trim($data['profession']) : null,
                        'default_adjust_percent' => !empty($data['default_adjust_percent']) ? floatval($data['default_adjust_percent']) : 0.00,
                        'income_family' => !empty($data['income_family']) ? trim($data['income_family']) : null,
                        'education_level' => !empty($data['education_level']) ? trim($data['education_level']) : null,
                        'sex' => !empty($data['sex']) ? strtoupper(trim($data['sex'])) : 'NI',
                        'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
                        'active' => true,
                    ];

                    // Validar nome obrigatório
                    if (empty($clientData['name'])) {
                        $skipped++;
                        $errors[] = "Linha {$rowNumber}: Nome é obrigatório";
                        continue;
                    }

                    // Validar sexo
                    if (!in_array($clientData['sex'], ['M', 'F', 'NI'])) {
                        $clientData['sex'] = 'NI';
                    }

                    if ($client) {
                        // Atualizar
                        $client->update($clientData);
                        $updated++;

                        // Limpar relacionamentos
                        $client->phones()->delete();
                        $client->emails()->delete();
                        $client->refs()->delete();
                    } else {
                        // Criar
                        $client = Client::create($clientData);
                        $created++;
                    }

                    // Processar telefones
                    if (!empty($data['phones'])) {
                        $phones = explode(';', $data['phones']);
                        foreach ($phones as $phone) {
                            $phone = trim($phone);
                            if (!empty($phone)) {
                                ClientPhone::create([
                                    'client_id' => $client->id,
                                    'phone' => ClientPhone::normalizePhone($phone),
                                ]);
                            }
                        }
                    }

                    // Processar e-mails
                    if (!empty($data['emails'])) {
                        $emails = explode(';', $data['emails']);
                        foreach ($emails as $email) {
                            $email = trim($email);
                            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                ClientEmail::create([
                                    'client_id' => $client->id,
                                    'email' => $email,
                                ]);
                            }
                        }
                    }

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Linha {$rowNumber}: " . $e->getMessage();
                    continue;
                }
            }

            DB::commit();

            $message = "Importação concluída! Criados: {$created}, Atualizados: {$updated}, Pulados: {$skipped}";
            
            if (!empty($errors)) {
                $message .= "\n\nErros:\n" . implode("\n", array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= "\n... e mais " . (count($errors) - 10) . " erros";
                }
            }

            return redirect()->route('clients.import.show')
                ->with('success', $message);

        } catch (ReaderException $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erro ao ler arquivo Excel: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erro durante importação: ' . $e->getMessage());
        }
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // Se já está no formato Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Tentar parsear
        try {
            $parsed = \Carbon\Carbon::parse($date);
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
