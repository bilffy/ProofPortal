<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Example Vue Component</div>

                    <div class="card-body">
                        I'm an example VUE component.
                    </div>
                    <div id="test-content">
                        <slot></slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { onMounted } from 'vue';
onMounted(() => console.log('Component mounted.'));
</script>