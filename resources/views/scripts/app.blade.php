@include('glosa::scripts.styles')
@include('glosa::scripts.templates.translations-view')

<script type="module">
    import { createApp, ref, onMounted } from "vue";

    try {
        @include('glosa::scripts.api')
        @include('glosa::scripts.components.translations-view')

        const app = createApp({
            components: { TranslationsView },
            setup() {
                // Hide loading fallback once mounted
                onMounted(() => {
                    document.getElementById("loading-fallback")?.remove();
                });
                return { currentPage: "TranslationsView" };
            }
        });

        app.mount("#app");

    } catch (err) {
        console.error("Glosa: Vue Init Error", err);
        document.body.innerHTML += '<div class="p-10 text-red-600 bg-white border m-10">Application Error: ' + err.message + '</div>';
    }
</script>
