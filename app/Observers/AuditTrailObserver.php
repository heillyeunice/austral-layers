<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\BusinessCustomer;
use App\Models\BusinessDocument;
use App\Models\BusinessExpense;
use App\Models\BusinessOrder;
use App\Models\BusinessOrderStageAssignment;
use App\Models\BusinessPayment;
use App\Models\PortfolioProject;
use App\Models\PrinterPreset;
use App\Models\PrintQuotation;
use App\Models\Product;
use App\Models\QuoteInquiry;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditTrailObserver
{
    private const IGNORED_ATTRIBUTES = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function created(Model $model): void
    {
        if ($model instanceof QuoteInquiry && request()->routeIs('quote.store')) {
            return;
        }

        $changes = [];

        foreach ($model->getAttributes() as $attribute => $value) {
            if ($this->shouldIgnore($attribute)) {
                continue;
            }

            $changes[$attribute] = [
                'old' => null,
                'new' => $this->safeValue($attribute, $value),
            ];
        }

        $this->record($model, 'created', $changes);
    }

    public function updated(Model $model): void
    {
        $changes = [];
        $original = $model->getRawOriginal();

        foreach ($model->getChanges() as $attribute => $value) {
            if ($this->shouldIgnore($attribute)) {
                continue;
            }

            $changes[$attribute] = [
                'old' => $this->safeValue($attribute, $original[$attribute] ?? null),
                'new' => $this->safeValue($attribute, $value),
            ];
        }

        if ($changes !== []) {
            $this->record($model, 'updated', $changes);
        }
    }

    public function deleted(Model $model): void
    {
        $changes = [];

        foreach ($model->getAttributes() as $attribute => $value) {
            if ($this->shouldIgnore($attribute)) {
                continue;
            }

            $changes[$attribute] = [
                'old' => $this->safeValue($attribute, $value),
                'new' => null,
            ];
        }

        $this->record($model, 'deleted', $changes);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', [
            'deleted_at' => [
                'old' => '[deleted]',
                'new' => null,
            ],
        ]);
    }

    private function record(Model $model, string $action, array $changes): void
    {
        $actor = Auth::user();

        if (! $actor instanceof User || ! in_array($actor->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true)) {
            return;
        }

        AuditLog::query()->create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role,
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => (string) $model->getKey(),
            'subject_label' => Str::limit($this->subjectLabel($model), 250),
            'changes' => $changes === [] ? null : $changes,
        ]);
    }

    private function shouldIgnore(string $attribute): bool
    {
        return in_array($attribute, self::IGNORED_ATTRIBUTES, true);
    }

    private function safeValue(string $attribute, mixed $value): mixed
    {
        if (Str::contains(Str::lower($attribute), ['password', 'token', 'secret'])) {
            return '[redacted]';
        }

        if (is_string($value)) {
            if (Str::startsWith(ltrim($value), ['{', '['])) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $value = $decoded;
                }
            }

            return is_array($value)
                ? Str::limit(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 2000)
                : Str::limit($value, 2000);
        }

        if (is_array($value)) {
            return Str::limit(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 2000);
        }

        return is_scalar($value) || $value === null ? $value : Str::limit((string) $value, 2000);
    }

    private function subjectLabel(Model $model): string
    {
        return match (true) {
            $model instanceof BusinessOrder => "Order {$model->order_number}: {$model->item_name}",
            $model instanceof BusinessOrderStageAssignment => 'Order #'.$model->order_id.' · '.(BusinessOrder::STATUSES[$model->stage] ?? $model->stage).' assignment',
            $model instanceof BusinessCustomer => 'Customer: '.$model->name,
            $model instanceof BusinessDocument => 'Document: '.$model->document_number,
            $model instanceof BusinessExpense => 'Expense: '.$model->title,
            $model instanceof BusinessPayment => 'Payment #'.$model->getKey().' for order #'.$model->order_id,
            $model instanceof PrintQuotation => '3D print quotation: '.$model->item_name,
            $model instanceof PrinterPreset => 'Printer preset: '.$model->brand.' '.$model->model,
            $model instanceof PortfolioProject => 'Portfolio: '.$model->title,
            $model instanceof Product => 'Product: '.$model->name,
            $model instanceof QuoteInquiry => 'Quote inquiry #'.$model->getKey(),
            $model instanceof SiteSetting => 'Site setting: '.$model->key,
            $model instanceof User => 'Staff account: '.$model->name,
            default => Str::headline(class_basename($model)).' #'.$model->getKey(),
        };
    }
}
