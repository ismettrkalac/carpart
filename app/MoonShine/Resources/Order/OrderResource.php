<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderActorType;
use App\Models\Order;
use App\MoonShine\Resources\Order\Pages\OrderDetailPage;
use App\MoonShine\Resources\Order\Pages\OrderFormPage;
use App\MoonShine\Resources\Order\Pages\OrderIndexPage;
use App\Services\Orders\OrderFulfillmentService;
use App\Services\Orders\OrderNoteService;
use App\Services\Orders\OrderShipmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\ToastType;

/**
 * @extends ModelResource<Order, OrderIndexPage, OrderFormPage, OrderDetailPage>
 *
 * All order-mutating actions below are thin wrappers that immediately
 * delegate to App\Services\Orders\* — this resource holds no business
 * rules of its own, only the admin-UI wiring (toasts/redirects/validation
 * messages). The same services are usable from any other controller.
 */
class OrderResource extends ModelResource
{
    protected string $model = Order::class;

    protected string $title = 'Orders';

    protected bool $withPolicy = true;

    // Newest orders first — sortDirection already defaults to DESC.
    protected string $sortColumn = 'created_at';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            OrderIndexPage::class,
            OrderFormPage::class,
            OrderDetailPage::class,
        ];
    }

    /**
     * @return list<string>
     */
    protected function search(): array
    {
        return ['uuid', 'email'];
    }

    #[AsyncMethod]
    public function transitionOrderStatus(Request $request, OrderFulfillmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string', Rule::enum(FulfillmentStatus::class)],
        ]);

        $service->transitionTo(
            order: $this->getItem(),
            to: FulfillmentStatus::from($validated['to']),
            actorType: OrderActorType::Staff,
            actorId: auth('moonshine')->id(),
        );

        return JsonResponse::make()
            ->toast('Order status updated.', ToastType::SUCCESS)
            ->redirect($this->getDetailPageUrl($this->getItem()->getKey()));
    }

    #[AsyncMethod]
    public function cancelOrder(Request $request, OrderFulfillmentService $service): JsonResponse
    {
        $note = $request->string('note')->trim()->value() ?: null;

        $service->cancel(
            order: $this->getItem(),
            actorType: OrderActorType::Staff,
            actorId: auth('moonshine')->id(),
            note: $note,
        );

        return JsonResponse::make()
            ->toast('Order cancelled.', ToastType::SUCCESS)
            ->redirect($this->getDetailPageUrl($this->getItem()->getKey()));
    }

    #[AsyncMethod]
    public function updateShipment(Request $request, OrderShipmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'carrier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $service->update(
            $this->getItem(),
            $validated['carrier'] ?: null,
            $validated['tracking_number'] ?: null,
            $validated['tracking_url'] ?: null,
        );

        return JsonResponse::make()
            ->toast('Shipment details saved.', ToastType::SUCCESS)
            ->redirect($this->getDetailPageUrl($this->getItem()->getKey()));
    }

    #[AsyncMethod]
    public function addOrderNote(Request $request, OrderNoteService $service): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $service->add($this->getItem(), $validated['body'], auth('moonshine')->id());

        return JsonResponse::make()
            ->toast('Note added.', ToastType::SUCCESS)
            ->redirect($this->getDetailPageUrl($this->getItem()->getKey()));
    }
}
