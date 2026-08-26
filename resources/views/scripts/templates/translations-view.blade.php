<script type="text/x-template" id="translations-view-template">
    <div class="flex flex-col h-full">
        @include('glosa::scripts.templates.partials.toolbar')
        @include('glosa::scripts.templates.partials.zero-state')
        @include('glosa::scripts.templates.partials.table')
        @include('glosa::scripts.templates.partials.modals.add-key')
        @include('glosa::scripts.templates.partials.modals.add-locale')
        @include('glosa::scripts.templates.partials.modals.edit-key')
        @include('glosa::scripts.templates.partials.modals.edit-locale')
        @include('glosa::scripts.templates.partials.modals.confirm')
        @include('glosa::scripts.templates.partials.modals.import')
        @include('glosa::scripts.templates.partials.modals.export')
        @include('glosa::scripts.templates.partials.toasts')
    </div>
</script>
