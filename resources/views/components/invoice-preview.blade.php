@props([
    'invoice',
    'user',
    'client',
    'qrCode',
    'iban',
])

<style>
    @include('invoices._document-styles', ['forPdf' => false])
</style>

<div
    x-data="{
        resizePreview() {
            const shell = this.$refs.shell;
            const frame = this.$refs.frame;
            const page = this.$refs.page;
            if (! shell || ! frame || ! page) {
                return;
            }

            const pageWidth = page.offsetWidth;
            const available = shell.clientWidth;

            if (available >= pageWidth) {
                frame.style.transform = 'none';
                frame.style.width = pageWidth + 'px';
                frame.style.margin = '0 auto';
                shell.style.height = page.offsetHeight + 'px';
                return;
            }

            const scale = available / pageWidth;
            frame.style.width = pageWidth + 'px';
            frame.style.transform = 'scale(' + scale + ')';
            frame.style.margin = '0';
            shell.style.height = (page.offsetHeight * scale) + 'px';
        },
    }"
    x-init="$nextTick(() => { resizePreview(); setTimeout(() => resizePreview(), 150); })"
    @resize.window="resizePreview()"
    class="invoice-preview-shell"
>
    <div x-ref="shell" class="invoice-preview-shell">
        <div x-ref="frame" class="invoice-preview-frame">
            <div x-ref="page" class="invoice-document invoice-document--preview">
                <div class="invoice-page">
                    <div class="invoice-box">
                        @include('invoices._document-content', compact('invoice', 'user', 'client', 'qrCode', 'iban'))
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
