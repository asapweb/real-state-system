<?php

use App\Http\Controllers\AccountMovementController;
use App\Http\Controllers\VoucherAssociationController;
use App\Http\Controllers\AccountMovementManagementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttachmentCategoryController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BillingDetailController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\CashAccountController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\CivilStatusController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\NationalityController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractServiceController;
use App\Http\Controllers\ContractClientController;
use App\Http\Controllers\ContractAdjustmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyClientController;
use App\Http\Controllers\TaxConditionController;
use App\Http\Controllers\PropertyTypeController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\VoucherCollectionController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ContractExpenseController;
use App\Http\Controllers\IndexTypeController;
use App\Http\Controllers\IndexValueController;
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\PropertyOwnerController;
use App\Http\Controllers\PropertyServiceController;
use App\Http\Controllers\RentalApplicationClientController;
use App\Http\Controllers\RentalApplicationController;
use App\Http\Controllers\RentalOfferController;
use App\Http\Controllers\RentalOfferServiceStatusController;
use App\Http\Middleware\AddApiVersionHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminCommandsController;
use App\Http\Controllers\BookletController;
use App\Http\Controllers\VoucherCalculationController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\AfipOperationTypeController;

Route::middleware(AddApiVersionHeader::class)->group(function () {
    Route::get('/health', fn() => response()->json(['status' => 'ok']));
    Route::get('/version', function () {
        return [
            'api_version' => config('app.api_version'),
        ];
    });

    Route::get('/document-types', [DocumentTypeController::class, 'index']);
    Route::get('/tax-conditions', [TaxConditionController::class, 'index']);
    Route::get('/civil-statuses', [CivilStatusController::class, 'index']);
    Route::get('/nationalities', [NationalityController::class, 'index']);
    Route::get('/countries', [CountryController::class, 'index']);
    Route::get('/property-types', [PropertyTypeController::class, 'index']);
    Route::get('/states', [StateController::class, 'index']);
    Route::get('/cities', [CityController::class, 'index']);
    Route::get('/neighborhoods', [NeighborhoodController::class, 'index']);
    Route::get('index-types', [IndexTypeController::class, 'index']);
    Route::get('/afip-operation-types', [AfipOperationTypeController::class, 'index']);

    Route::middleware(['auth:sanctum'])->group(function () {
        // Index Types - CRUD completo
        Route::prefix('index-types')->group(function () {
            Route::get('/', [IndexTypeController::class, 'index']);
            Route::post('/', [IndexTypeController::class, 'store']);
            Route::get('/{indexType}', [IndexTypeController::class, 'show']);
            Route::put('/{indexType}', [IndexTypeController::class, 'update']);
            Route::delete('/{indexType}', [IndexTypeController::class, 'destroy']);
        });

        // Index Values - CRUD completo
        Route::prefix('index-values')->group(function () {
            Route::get('/', [IndexValueController::class, 'index']);
            Route::post('/', [IndexValueController::class, 'store']);
            Route::get('/{indexValue}', [IndexValueController::class, 'show']);
            Route::put('/{indexValue}', [IndexValueController::class, 'update']);
            Route::delete('/{indexValue}', [IndexValueController::class, 'destroy']);

            // Nuevas rutas para funcionalidades adicionales
            Route::get('/by-mode/{calculationMode}', [IndexValueController::class, 'getByCalculationMode']);
            Route::get('/latest/{indexTypeId}', [IndexValueController::class, 'getLatestValue']);
        });

        // Attachments
        Route::prefix('attachments')->group(function () {
            Route::get('/{type}/{id}', [AttachmentController::class, 'index']);
            Route::post('/{type}/{id}', [AttachmentController::class, 'store']);
            Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
        });
         // Attachment Categories
        Route::prefix('attachment-categories')->group(function () {
            Route::get('/', [AttachmentCategoryController::class, 'index']); // paginado, filtro por context
            Route::get('/all', [AttachmentCategoryController::class, 'all']); // sin paginación, opcional
            Route::get('/{attachmentCategory}', [AttachmentCategoryController::class, 'show']);
            Route::post('/', [AttachmentCategoryController::class, 'store']);
            Route::put('/{attachmentCategory}', [AttachmentCategoryController::class, 'update']);
            Route::delete('/{attachmentCategory}', [AttachmentCategoryController::class, 'destroy']);
        });

        // Clients
        Route::prefix('clients')->group(function () {
            Route::get('/', [ClientController::class, 'index']);           // Listado paginado
            Route::post('/', [ClientController::class, 'store']);          // Alta
            Route::get('{client}', [ClientController::class, 'show']);     // Detalle
            Route::put('{client}', [ClientController::class, 'update']);   // Modificación
            Route::delete('{client}', [ClientController::class, 'destroy']); // Baja

            // Cuenta corriente
            Route::get('{client}/account-movements', [AccountMovementController::class, 'index']);
            Route::get('{client}/account-balances', [AccountMovementController::class, 'balances']);
            Route::post('{client}/account-movements/initial-balance', [AccountMovementManagementController::class, 'setInitialBalance']);

            // Documentos adjuntos
            Route::post('{client}/attachments', [ClientController::class, 'addAttachment']);
        });

        // Movimientos de caja
        Route::get('cash-movements', [CashMovementController::class, 'index']);

        // Propiedades
        Route::prefix('properties')->group(function () {
            Route::get('/', [PropertyController::class, 'index']);          // Listado
            Route::post('/', [PropertyController::class, 'store']);         // Alta
            Route::get('{property}', [PropertyController::class, 'show']);  // Detalle
            Route::put('{property}', [PropertyController::class, 'update']); // Modificación
            Route::delete('{property}', [PropertyController::class, 'destroy']); // Baja

            // Relaciones auxiliares (opcionalmente anidadas)
            Route::post('{property}/attachments', [PropertyController::class, 'addAttachment']);
            Route::get('{property}/owners', [PropertyOwnerController::class, 'index']);
            Route::post('{property}/owners', [PropertyOwnerController::class, 'store']);
            Route::put('{property}/owners/{owner}', [PropertyOwnerController::class, 'update']);
            Route::delete('{property}/owners/{owner}', [PropertyOwnerController::class, 'destroy']);

            Route::get('{property}/services', [PropertyServiceController::class, 'index']);
            Route::post('{property}/services', [PropertyServiceController::class, 'store']);
            Route::put('{property}/services/{service}', [PropertyServiceController::class, 'update']);
            Route::put('{delete}/services/{service}', [PropertyServiceController::class, 'destroy']);
        });

        // Oferta de alquiler
        Route::prefix('rental-offers')->group(function () {
            Route::get('/', [RentalOfferController::class, 'index']);          // Listado
            Route::post('/', [RentalOfferController::class, 'store']);         // Alta
            Route::get('{rentalOffer}', [RentalOfferController::class, 'show']);  // Detalle
            Route::put('{rentalOffer}', [RentalOfferController::class, 'update']); // Modificación
            Route::delete('{rentalOffer}', [RentalOfferController::class, 'destroy']); // Baja

            // Relaciones auxiliares
            Route::get('{rentalOffer}/attachments', [AttachmentController::class, 'index']);

            Route::get('{rentalOffer}/services', [RentalOfferServiceStatusController::class, 'index']);
            Route::put('rental-offer-service-statuses/{rentalOfferServiceStatus}', [RentalOfferServiceStatusController::class, 'update']);

        });

         // Solicitudes de alquiler
        Route::prefix('rental-applications')->group(function () {
            Route::get('/', [RentalApplicationController::class, 'index']);          // Listado
            Route::post('/', [RentalApplicationController::class, 'store']);         // Alta
            Route::get('{rentalApplication}', [RentalApplicationController::class, 'show']);  // Detalle
            Route::put('{rentalApplication}', [RentalApplicationController::class, 'update']); // Modificación
            Route::delete('{rentalApplication}', [RentalApplicationController::class, 'destroy']); // Baja

            // Relaciones auxiliares
            Route::get('{rentalApplication}/clients', [RentalApplicationClientController::class, 'index']);
            Route::post('{rentalApplication}/clients', [RentalApplicationClientController::class, 'store']);
            Route::put('{rentalApplication}/clients/{rentalApplicationClient}', [RentalApplicationClientController::class, 'update']);
            Route::delete('{rentalApplication}/clients/{rentalApplicationClient}', [RentalApplicationClientController::class, 'destroy']);

            Route::get('{rentalApplication}/attachments', [AttachmentController::class, 'index']);
        });

        Route::get('contract-adjustments/global', [ContractAdjustmentController::class, 'globalIndex']);

        // Contratos
        Route::prefix('contracts')->group(function () {
            Route::get('/', [ContractController::class, 'index']);
            Route::post('/', [ContractController::class, 'store']);
            Route::get('{contract}', [ContractController::class, 'show']);
            Route::put('{contract}', [ContractController::class, 'update']);
            Route::delete('{contract}', [ContractController::class, 'destroy']);

            // 🔸 Contract Services
            Route::get('{contract}/services', [ContractServiceController::class, 'index']);
            Route::post('{contract}/services', [ContractServiceController::class, 'store']);
            Route::get('{contractService}', [ContractServiceController::class, 'show']);
            Route::put('{contractService}', [ContractServiceController::class, 'update']);
            Route::delete('{contractService}', [ContractServiceController::class, 'destroy']);

            Route::get('{contract}/expenses', [ContractExpenseController::class, 'index']);
            Route::post('{contract}/expenses', [ContractExpenseController::class, 'store']);
            Route::put('{contract}/expenses/{expense}', [ContractExpenseController::class, 'update']);
            Route::delete('{contract}/expenses/{expense}', [ContractExpenseController::class, 'destroy']);

            // Adjuntos de servicio
            Route::get('{contractService}/attachments', [AttachmentController::class, 'index']);

            // 🔸 Contract Clients
            Route::get('{contract}/clients', [ContractClientController::class, 'index']);
            Route::post('{contract}/clients', [ContractClientController::class, 'store']);
            Route::get('{contract}/clients/{contractClient}', [ContractClientController::class, 'show']);
            Route::put('{contract}/clients/{contractClient}', [ContractClientController::class, 'update']);
            Route::delete('{contract}/clients/{contractClient}', [ContractClientController::class, 'destroy']);

            // 🔸 Contract Adjustments
            Route::get('{contract}/adjustments', [ContractAdjustmentController::class, 'index']);
            Route::post('{contract}/adjustments', [ContractAdjustmentController::class, 'store']);
            Route::get('{contract}/adjustments/{adjustment}', [ContractAdjustmentController::class, 'show']);
            Route::put('{contract}/adjustments/{adjustment}', [ContractAdjustmentController::class, 'update']);
            Route::delete('{contract}/adjustments/{adjustment}', [ContractAdjustmentController::class, 'destroy']);
            Route::patch('{contract}/adjustments/{adjustment}/value', [ContractAdjustmentController::class, 'updateValue']);
            Route::post('{contract}/adjustments/{adjustment}/apply', [ContractAdjustmentController::class, 'apply']);
            // 🔸 Contract Adjustments
            Route::get('{contract}/services', [ContractServiceController::class, 'index']);
            Route::post('{contract}/services', [ContractServiceController::class, 'store']);
            Route::get('{contract}/services/{contractService}', [ContractServiceController::class, 'show']);
            Route::put('{contract}/services/{contractService}', [ContractServiceController::class, 'update']);
            Route::delete('{contract}/services/{contractService}', [ContractServiceController::class, 'destroy']);
        });

        Route::prefix('collections')->group(function () {
            Route::get('/', [VoucherCollectionController::class, 'index']);
            // Route::get('{collection}', [CollectionController::class, 'show']);
            // Route::post('/', [CollectionController::class, 'store']);
            Route::post('generate', [VoucherCollectionController::class, 'generate']);
            Route::post('preview', [VoucherCollectionController::class, 'preview']);
            // Route::post('{collection}/mark-as-paid', [CollectionController::class, 'markAsPaid']);
            // Route::post('{collection}/cancel', [CollectionController::class, 'cancel']);
        });

        // Rutas para vouchers (nuevo sistema unificado)
        Route::prefix('vouchers')->group(function () {
            Route::get('/with-pending-by-client', [VoucherController::class, 'applicable']);
            Route::get('/associable', [VoucherAssociationController::class, 'associable']);
            Route::get('/', [VoucherController::class, 'index']);
            Route::get('{voucher}', [VoucherController::class, 'show']);
            Route::post('/', [VoucherController::class, 'store']);
            Route::put('{voucher}', [VoucherController::class, 'update']);
            Route::delete('{voucher}', [VoucherController::class, 'destroy']);
            Route::post('{voucher}/issue', [VoucherController::class, 'issue']);
            Route::post('{voucher}/mark-as-paid', [VoucherController::class, 'markAsPaid']);
            Route::post('{voucher}/cancel', [VoucherController::class, 'cancel']);
            Route::post('generate-collections', [VoucherController::class, 'generateCollections']);
            Route::post('preview-collections', [VoucherController::class, 'previewCollections']);
            Route::post('preview-totals', [VoucherCalculationController::class, 'previewTotals']);
        });

        Route::apiResource('tax-rates', TaxRateController::class)->only(['index']);


        // Relaciones auxiliares
        // Route::get('{contractService}/attachments', [AttachmentController::class, 'index']);

    });



    Route::post('voucher-associations', [VoucherAssociationController::class, 'store']);








    Route::middleware('auth:sanctum')->group(function () {
        // Route::get('/states/{country}', [ReferenceDataController::class, 'getStates']);
        // Route::get('/cities/{state}', [ReferenceDataController::class, 'getCities']);
        // Route::get('/neighborhoods/{city}', [ReferenceDataController::class, 'getNeighborhoods']);
        // Route::get('/property-types', [ReferenceDataController::class, 'getPropertyTypes']);

        Route::apiResource('clients', ClientController::class);
        Route::apiResource('clients.billing-details', BillingDetailController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::apiResource('billing-details', BillingDetailController::class)->except(['index', 'store', 'create']);

        Route::apiResource('properties', PropertyController::class);
        Route::apiResource('properties.clients', PropertyClientController::class)->only(['index', 'store', 'update', 'destroy']);


        // Route::apiResource('properties.attachments', AttachmentController::class)->only(['index', 'store', 'show', 'destroy']);
        // Route::apiResource('clients.attachments', AttachmentController::class)->only(['index', 'store', 'show', 'destroy']);
        // Route::apiResource('contracts.attachments', AttachmentController::class)->only(['index', 'store', 'show', 'destroy']);
        // Route::apiResource('attachment-categories', AttachmentCategoryController::class);
    });




    Route::apiResource('departments', DepartmentController::class)->middleware('auth:sanctum');
    Route::get('document_types', [DocumentTypeController::class, 'index'])->middleware('auth:sanctum');
    Route::get('users', [UserController::class, 'index'])->middleware('auth:sanctum');
    Route::post('users', [UserController::class, 'store'])->middleware('auth:sanctum');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('auth:sanctum');
    Route::get('/users/{user}/roles', [UserController::class, 'getUserRoles'])->middleware('auth:sanctum'); // Permiso para ver los roles del usuario
    Route::put('/users/{user}/roles', [UserController::class, 'updateUserRoles'])->middleware('auth:sanctum'); // Permiso para actualizar los roles del usuario
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('auth:sanctum');

    // Ruta específica para cuentas activas, antes de la apiResource
    Route::get('cash-accounts/active', [CashAccountController::class, 'active'])->name('cash-accounts.active');
    Route::apiResource('cash-accounts', CashAccountController::class);

    Route::get('payment-methods/active', [PaymentMethodController::class, 'active']);
    Route::apiResource('payment-methods', PaymentMethodController::class);

    Route::apiResource('cash-movements', CashMovementController::class);

    Route::post('appointments/notifications/call', [AppointmentController::class, 'notificationCall'])->middleware('auth:sanctum');
    Route::post('appointments/{id}/notifications/new', [AppointmentController::class, 'notificationAppontmentStatus'])->middleware('auth:sanctum');
    Route::post('appointments/{appointment}/status', [AppointmentController::class, 'status'])->middleware('auth:sanctum');
    Route::apiResource('appointments', AppointmentController::class)->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('roles', RoleController::class)->except(['edit', 'create']);
        Route::get('/roles/{role}/permissions', [RoleController::class, 'getRolePermissions']); // Puedes usar 'ver_roles', 'gestionar_permisos' o un permiso más específico
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updateRolePermissions']); // Permiso para asignar/revocar permisos
        Route::get('/permissions', [RoleController::class, 'permissions']);
    });



    Route::post('/clients/validate-document', [ClientController::class, 'validateDocument']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/change-password', [UserController::class, 'changePassword'])->middleware('auth:sanctum');

    Route::post('/users/{user}/departments/{department}', [UserController::class, 'attachDepartment']);


    // Route::get('/', function (Request $request) {
    //     return "REAL STATE API";
    // });

    Route::get('/user', function (Request $request) {
        return $request->user()->load('departments');
    })->middleware('auth:sanctum');

    Route::get('/test', function (Request $request) {
        return 'adsadsa';

    });

    // Comandos Administrativos - Requieren permisos especiales
    Route::prefix('admin/commands')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/available', [AdminCommandsController::class, 'getAvailableCommands']);
        Route::post('/sync-indices', [AdminCommandsController::class, 'syncIndices']);
        Route::post('/assign-index-values', [AdminCommandsController::class, 'assignIndexValues']);
        Route::get('/logs', [AdminCommandsController::class, 'getRecentLogs']);
    });

    Route::get('booklets', [BookletController::class, 'index']);
});
