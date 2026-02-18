<?php

namespace App\Http\Controllers;

use App\Exports\Glosa\GlosaExcelErrorsValidationExport;
use App\Exports\InvoiceAudit\InvoiceAuditExcelExport;
use App\Exports\Service\ServiceListExcelExport;
use App\Http\Resources\InvoiceAudit\InvoiceAuditListResource;
use App\Http\Resources\InvoiceAudit\InvoiceAuditPaginateBatcheResource;
use App\Http\Resources\InvoiceAudit\InvoiceAuditPaginateInvoiceAuditResource;
use App\Http\Resources\InvoiceAudit\InvoiceAuditPaginateInvoiceAuditAllResource;
use App\Http\Resources\InvoiceAudit\InvoiceAuditPaginatePatientResource;
use App\Http\Resources\InvoiceAudit\InvoiceAuditPaginateServiceResource;
use App\Http\Resources\InvoiceAudit\InvoiceAuditPaginateThirdsResource;
use App\Jobs\BrevoProcessSendEmail;
use App\Services\ProcessBatchService;
use App\Jobs\InvoiceAudit\ValidateInvoiceAuditCsvJob;
use App\Repositories\AssignmentRepository;
use App\Repositories\CodeGlosaRepository;
use App\Repositories\InvoiceAuditRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\ThirdRepository;
use App\Repositories\UserRepository;
use App\Services\CacheService;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;
use App\Helpers\Constants;
use App\Models\ProcessBatch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceAuditController extends Controller
{
    use HttpResponseTrait;

    private $key_redis_project;

    public function __construct(
        protected InvoiceAuditRepository $invoiceAuditRepository,
        protected ThirdRepository $thirdRepository,
        protected PatientRepository $patientRepository,
        protected CodeGlosaRepository $codeGlosaRepository,
        protected ServiceRepository $serviceRepository,
        protected UserRepository $userRepository,
        protected AssignmentRepository $assignmentRepository,
        protected CacheService $cacheService,

    ) {
        $this->key_redis_project = env('KEY_REDIS_PROJECT');
    }

    public function list(Request $request)
    {
        return $this->execute(function () use ($request) {

            $invoiceAudit = $this->invoiceAuditRepository->list($request->all());
            $tableData = InvoiceAuditListResource::collection($invoiceAudit);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $invoiceAudit->lastPage(),
                'totalData' => $invoiceAudit->total(),
                'totalPage' => $invoiceAudit->perPage(),
                'currentPage' => $invoiceAudit->currentPage(),
            ];
        });
    }

    public function paginateBatche(Request $request)
    {
        return $this->execute(function () use ($request) {

            $data = $this->invoiceAuditRepository->paginateBatche($request->all());
            $tableData = InvoiceAuditPaginateBatcheResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function paginateThirds(Request $request, $assignment_batche_id)
    {
        return $this->execute(function () use ($request, $assignment_batche_id) {

            $request['assignment_batche_id'] = $assignment_batche_id;

            $data = $this->invoiceAuditRepository->paginateThirds($request->all());
            $tableData = InvoiceAuditPaginateThirdsResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function paginateInvoiceAudit(Request $request, $assignment_batche_id, $third_id)
    {
        return $this->execute(function () use ($request, $assignment_batche_id, $third_id) {

            $request['assignment_batch_id'] = $assignment_batche_id;

            $request['third_id'] = $third_id;

            $data = $this->invoiceAuditRepository->paginateInvoiceAudit($request->all());
            $tableData = InvoiceAuditPaginateInvoiceAuditResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function paginateInvoiceAuditAll(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->invoiceAuditRepository->paginateInvoiceAuditAll($request->all());
            $tableData = InvoiceAuditPaginateInvoiceAuditAllResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function paginatePatient(Request $request, $assignment_batche_id, $third_id, $invoice_audit_id)
    {
        return $this->execute(function () use ($request, $assignment_batche_id, $third_id, $invoice_audit_id) {

            $request['assignment_batche_id'] = $assignment_batche_id;

            $request['third_id'] = $third_id;

            $request['invoice_audit_id'] = $invoice_audit_id;

            $data = $this->invoiceAuditRepository->paginatePatient($request->all());
            $tableData = InvoiceAuditPaginatePatientResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function getInformationSheet(Request $request, $third_id, $invoice_audit_id, $patient_id)
    {
        return $this->execute(function () use ($third_id, $invoice_audit_id, $patient_id, $request) {

            $invoice_audit = $this->invoiceAuditRepository->find($invoice_audit_id);
            $third = $this->thirdRepository->find($third_id);

            $patient = $this->patientRepository->find($patient_id);
            $patient = new InvoiceAuditPaginatePatientResource($patient);

            $value_glosa = $invoice_audit->services->sum('value_glosa');
            $value_approved = $invoice_audit->services->sum('value_approved');

            $request['third_id'] = $third_id;
            $request['invoice_audit_id'] = $invoice_audit_id;

            $assignment = $this->assignmentRepository->searchOne($request->all());

            $invoice_audit['total_value'] = formatNumber($invoice_audit['total_value']);
            $invoice_audit['codes_glosa'] = $invoice_audit->glosas->pluck('code_glosa_id')->unique()->implode(', ');

            return [
                'code' => 200,
                'assignment' => $assignment,
                'data' => [
                    'invoice_audit' => $invoice_audit,
                    'third' => $third,
                    'patient' => $patient,
                    'value_glosa' => formatNumber($value_glosa),
                    'value_approved' => formatNumber($value_approved),
                ],
            ];
        });
    }

    public function getServices(Request $request, $invoice_audit_id, $patient_id)
    {
        return $this->execute(function () use ($request, $invoice_audit_id, $patient_id) {

            $request['invoice_audit_id'] = $invoice_audit_id;

            $request['patient_id'] = $patient_id;

            $data = $this->invoiceAuditRepository->paginateServices($request->all());
            $tableData = InvoiceAuditPaginateServiceResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function exportListServicesExcel(Request $request)
    {
        return $this->execute(function () use ($request) {
            $services = $this->invoiceAuditRepository->paginateServices($request->all());

            $excel = Excel::raw(new ServiceListExcelExport($services), \Maatwebsite\Excel\Excel::XLSX);

            $excelBase64 = base64_encode($excel);

            return [
                'code' => 200,
                'excel' => $excelBase64,
            ];
        });
    }

    public function exportDataToGlosasImportCsv(Request $request)
    {
        return $this->execute(function () use ($request) {

            ini_set('memory_limit', '1024M');

            $user_id = $request->input('user_id');
            $services = $this->serviceRepository->getServicesToImportGlosas($request->all());

            $glosses = $this->codeGlosaRepository->list(
                [
                    'typeData' => 'all',
                    'is_active' => 1,
                ]
            );
            $attachedData = [
                [
                    'id' => $user_id,
                ],
            ];

            $excel = Excel::raw(new InvoiceAuditExcelExport($services, $glosses, $attachedData, $request->all()), \Maatwebsite\Excel\Excel::XLSX);

            $excelBase64 = base64_encode($excel);

            // Obtener el objeto User a partir del ID
            $user = $this->userRepository->find($user_id);

            if ($user) {
                // Enviar notificación
                // $user->notify(new BellNotification($data));

                // Enviar el correo usando el job de Brevo
                BrevoProcessSendEmail::dispatch(
                    emailTo: [
                        [
                            'name' => $user->full_name,
                            'email' => $user->email,
                        ],
                    ],
                    subject: 'Exportacion de servicios',
                    templateId: 9,  // El ID de la plantilla de Brevo que quieres usar
                    params: [
                        'full_name' => $user->full_name,
                        'subtitle' => 'informacion de los servicios, descargue el archivo donde se muestra la informacion de los servicios',
                        'bussines_name' => $user->company?->name,
                    ],
                    attachments: [
                        [
                            'name' => 'Servicios.xlsx',
                            'content' => $excelBase64,
                        ],
                    ],
                );
            }

            return [
                'code' => 200,
                'excel' => $excelBase64,
            ];
        });
    }

    public function excelErrorsValidation(Request $request)
    {
        return $this->execute(function () use ($request) {

            $user_id = $request->input('user_id');

            // Obtener los mensajes de errores de las validaciones
            $data = $this->invoiceAuditRepository->getValidationsErrorMessages($user_id);

            $excel = Excel::raw(new GlosaExcelErrorsValidationExport($data), \Maatwebsite\Excel\Excel::XLSX);

            $excelBase64 = base64_encode($excel);

            return [
                'code' => 200,
                'excel' => $excelBase64,
            ];
        });
    }

    public function successFinalizedAudit(Request $request)
    {
        $request = $request->all();

        return $this->execute(function () use ($request) {

            $this->assignmentRepository->changeStatusAssigmentMasive($request);

            $this->cacheService->clearByPrefix($this->key_redis_project . 'string:assignments_paginate_count_all_data*');
            $this->cacheService->clearByPrefix($this->key_redis_project . 'string:invoice_audits_paginateThirds*');
            $this->cacheService->clearByPrefix($this->key_redis_project . 'string:invoice_audits_paginateBatche*');

            return [
                'code' => 200,
                'message' => 'Auditoria finalizada con exito',
            ];
        });
    }

    public function successReturnAudit(Request $request)
    {
        $request = $request->all();

        return $this->execute(function () use ($request) {

            $this->assignmentRepository->changeStatusAssigmentMasiveReturn($request);

            $this->cacheService->clearByPrefix($this->key_redis_project . 'string:assignments_paginate_count_all_data*');
            $this->cacheService->clearByPrefix($this->key_redis_project . 'string:invoice_audits_paginateThirds*');
            $this->cacheService->clearByPrefix($this->key_redis_project . 'string:invoice_audits_paginateBatche*');

            return [
                'code' => 200,
                'message' => 'Auditoria finalizada con exito',
            ];
        });
    }

    public function uploadCsv(Request $request)
    {
        return $this->runTransaction(function () use ($request) {
            // 1. Recolección de Datos
            $company_id = $request->input('company_id');
            $user_id = $request->input('user_id');
            $uploadedFile = $request->file('file');
            $batchId = Str::uuid()->toString();

            // 2. Normalización del Nombre del Archivo
            $fileNameWithExtension = strtolower($uploadedFile->getClientOriginalName());
            $fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
            $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $uniqueFileName = $fileName . '_' . time() . '.' . $fileExtension;

            // 3. Almacenamiento Físico (Carpeta Temporal Aislada)
            $tempSubfolder = 'temp/invoice_audit/csv/' . $batchId;
            $filePath = $uploadedFile->storeAs($tempSubfolder, $uniqueFileName, Constants::DISK_FILES);
            $fullPath = storage_path('app/public/' . $filePath);

            // 4. Preparar Metadata
            $metadata = [
                'file_name' => $uniqueFileName,
                'original_name' => $fileNameWithExtension,
                'file_size' => $uploadedFile->getSize(),
                'path' => $filePath,
                'full_path' => $fullPath,
                'disk' => Constants::DISK_FILES,
                'user_id' => $user_id,
                'company_id' => $company_id,
                'started_at' => now()->toDateTimeString(),
                'status' => 'uploaded',
                'process_batch_id' => $batchId,
            ];

            // 5. Redis: Estado en Tiempo Real
            $redis = Redis::connection('redis_6380');

            // Usamos hset en lugar de hmset (estándar moderno)
            $redis->hmset("batch:{$batchId}:metadata", $metadata);

            // Expiración de seguridad (24 horas)
            $redis->expire("batch:{$batchId}:metadata", 86400);

            // 6. MySQL: Registro Histórico
            ProcessBatch::create([
                'id' => $batchId,
                'batch_id' => $batchId,
                'company_id' => $company_id,
                'user_id' => $user_id,
                'total_records' => 0,
                'processed_records' => 0,
                'error_count' => 0,
                'status' => 'queued',
                'metadata' => json_encode($metadata),
            ]);

            // 7. Selección de Cola y Dispatch (Fase 1: Validación ZIP)
            $selectedQueue = ProcessBatchService::selectAvailableQueueRoundRobin(Constants::AVAILABLE_QUEUES_TO_IMPORTS_FILING_ZIP);

            // AQUÍ EL CAMBIO DE NOMBRE: ValidateFilingZipJob
            ValidateInvoiceAuditCsvJob::dispatch($batchId, $selectedQueue)->onQueue($selectedQueue);

            return [
                'code' => 200,
                'message' => 'Archivo CSV subido. Iniciando validación de estructura.',
                'batch_id' => $batchId,
                'status' => 'success',
            ];
        });
    }
}
