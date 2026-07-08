@php
    $invoice = $invoice ?? null;
    $suggestedNumber = $suggestedNumber ?? ($invoiceNumber ?? '');
    $issueDate = old('issue_date', $invoice?->issue_date?->format('Y-m-d') ?? ($issueDate ?? now()->format('Y-m-d')));
    $defaultDueDate = old('due_date', $invoice?->due_date?->format('Y-m-d') ?? ($defaultDueDate ?? now()->addMonth()->day(15)->format('Y-m-d')));
    $invoiceNumber = old('number', $invoice?->number ?? ($invoiceNumber ?? ''));
    $variableSymbol = old('variable_symbol', $variableSymbol ?? ($invoice ? ($invoice->variable_symbol ?? $invoice->effectiveVariableSymbol()) : ''));
    $defaultItems = old('items', $invoice?->items->map(fn ($item) => [
        'description' => $item->description,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
    ])->toArray() ?? [['description' => '', 'quantity' => 1, 'unit_price' => 0]]);
    $productOptions = ($products ?? collect())->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'unit_price' => (float) $product->unit_price,
    ])->values();
@endphp

<form method="POST" action="{{ $action }}"
      x-data="invoiceForm(
          {{ json_encode($defaultItems) }},
          {{ $isVatPayer ? 'true' : 'false' }},
          {{ json_encode($issueDate) }},
          {{ json_encode($defaultDueDate) }},
          {{ json_encode($invoiceNumber) }},
          {{ json_encode($variableSymbol) }},
          {{ json_encode($suggestedNumber) }},
          {{ json_encode($productOptions) }}
      )">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="md:col-span-2">
            <x-input-label for="number" value="Číslo faktury" />
            <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-start">
                <input
                    id="number"
                    name="number"
                    type="text"
                    required
                    x-model="invoiceNumber"
                    @input="syncVariableSymbol()"
                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                @if (! $invoice)
                    <button
                        type="button"
                        @click="applySuggestedNumber()"
                        class="shrink-0 inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Navrhnout další
                    </button>
                @endif
            </div>
            <p class="mt-1 text-xs text-gray-500">Číslo si můžete upravit. Tlačítko „Navrhnout další“ použije nejvyšší existující číslo + 1.</p>
            <x-input-error :messages="$errors->get('number')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="client_id" value="Klient" />
            <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="">Vyberte klienta</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id', $invoice?->client_id) == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="issue_date" value="Datum vystavení" />
            <input
                id="issue_date"
                name="issue_date"
                type="date"
                required
                x-model="issueDate"
                @change="updateDueDate()"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
            >
            <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="due_date" value="Datum splatnosti" />
            <input
                id="due_date"
                name="due_date"
                type="date"
                required
                x-model="dueDate"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
            >
            <p class="mt-1 text-xs text-gray-500">Výchozí: 15. den následujícího měsíce po vystavení.</p>
            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="variable_symbol" value="Variabilní symbol" />
            <input
                id="variable_symbol"
                name="variable_symbol"
                type="text"
                x-model="variableSymbol"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
            >
            <x-input-error :messages="$errors->get('variable_symbol')" class="mt-2" />
        </div>
    </div>

    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-gray-900">Položky</h3>
            <button type="button" @click="addItem()" class="text-sm text-brand hover:underline">+ Přidat položku</button>
        </div>

        <template x-for="(item, index) in items" :key="index">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                <div class="md:col-span-12" x-show="products.length">
                    <x-input-label value="Z ceníku" />
                    <select
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        @change="applyProduct(item, $event.target.value); $event.target.value = ''"
                    >
                        <option value="">— vybrat z ceníku —</option>
                        <template x-for="product in products" :key="product.id">
                            <option :value="product.id" x-text="product.name + ' (' + formatMoney(product.unit_price) + ')'"></option>
                        </template>
                    </select>
                </div>
                <div class="md:col-span-5">
                    <x-input-label value="Popis" />
                    <input type="text" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                           x-model="item.description" :name="`items[${index}][description]`" required>
                </div>
                <div class="md:col-span-2">
                    <x-input-label value="Množství" />
                    <input type="number" step="0.01" min="0.01" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                           x-model.number="item.quantity" :name="`items[${index}][quantity]`" required>
                </div>
                <div class="md:col-span-2">
                    <x-input-label value="Cena/jedn." />
                    <input type="number" step="0.01" min="0" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                           x-model.number="item.unit_price" :name="`items[${index}][unit_price]`" required>
                </div>
                <div class="md:col-span-2">
                    <x-input-label value="Celkem" />
                    <div class="mt-2 font-medium" x-text="formatMoney(lineTotal(item))"></div>
                </div>
                <div class="md:col-span-1 flex items-end">
                    <button type="button" @click="removeItem(index)" class="text-red-600 text-sm hover:underline" x-show="items.length > 1">Smazat</button>
                </div>
            </div>
        </template>
        <x-input-error :messages="$errors->get('items')" class="mt-2" />
    </div>

    <div class="mb-6 max-w-sm ml-auto space-y-1 text-right">
        <div>Mezisoučet: <span x-text="formatMoney(subtotal())"></span></div>
        <div x-show="isVatPayer">DPH 21 %: <span x-text="formatMoney(vatAmount())"></span></div>
        <div class="text-lg font-semibold">Celkem: <span x-text="formatMoney(total())"></span></div>
        <p class="text-sm text-gray-500" x-show="!isVatPayer">Neplátce DPH — bez DPH</p>
    </div>

    <div class="mb-6">
        <x-input-label for="notes" value="Poznámka" />
        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $invoice?->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="flex justify-end gap-2 pb-2">
        <a href="{{ $invoice ? route('invoices.show', $invoice) : route('invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Zrušit</a>
        <x-primary-button>Uložit fakturu</x-primary-button>
    </div>
</form>

<script>
    function invoiceForm(initialItems, isVatPayer, initialIssueDate, initialDueDate, initialNumber, initialVariableSymbol, suggestedNumber, products) {
        return {
            items: initialItems.length ? initialItems : [{ description: '', quantity: 1, unit_price: 0 }],
            isVatPayer: isVatPayer,
            issueDate: initialIssueDate,
            dueDate: initialDueDate,
            invoiceNumber: initialNumber,
            variableSymbol: initialVariableSymbol,
            suggestedNumber: suggestedNumber,
            products: products || [],
            addItem() {
                this.items.push({ description: '', quantity: 1, unit_price: 0 });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            applyProduct(item, productId) {
                const product = this.products.find(p => String(p.id) === String(productId));

                if (! product) {
                    return;
                }

                item.description = product.name;
                item.unit_price = product.unit_price;

                if (! item.quantity || Number(item.quantity) <= 0) {
                    item.quantity = 1;
                }
            },
            lineTotal(item) {
                return Math.round((Number(item.quantity) || 0) * (Number(item.unit_price) || 0) * 100) / 100;
            },
            subtotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            vatAmount() {
                return this.isVatPayer ? Math.round(this.subtotal() * 0.21 * 100) / 100 : 0;
            },
            total() {
                return Math.round((this.subtotal() + this.vatAmount()) * 100) / 100;
            },
            formatMoney(value) {
                return new Intl.NumberFormat('cs-CZ', { style: 'currency', currency: 'CZK' }).format(value || 0);
            },
            computeDueDate(issueDate) {
                if (! issueDate) {
                    return '';
                }

                const [year, month, day] = issueDate.split('-').map(Number);
                const date = new Date(year, month - 1, day);
                date.setMonth(date.getMonth() + 1);
                date.setDate(15);

                const dueYear = date.getFullYear();
                const dueMonth = String(date.getMonth() + 1).padStart(2, '0');
                const dueDay = String(date.getDate()).padStart(2, '0');

                return `${dueYear}-${dueMonth}-${dueDay}`;
            },
            updateDueDate() {
                this.dueDate = this.computeDueDate(this.issueDate);
            },
            syncVariableSymbol() {
                const digits = (this.invoiceNumber || '').replace(/\D/g, '').slice(0, 10);

                if (digits) {
                    this.variableSymbol = digits;
                }
            },
            applySuggestedNumber() {
                this.invoiceNumber = this.suggestedNumber;
                this.syncVariableSymbol();
            },
        };
    }
</script>
