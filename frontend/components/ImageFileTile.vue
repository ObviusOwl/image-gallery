<script>

export default {
    name: "ImageFileTile",
    props: {
        file: { type: Object, required: true },
    },
    data: function() {
        return {
        };
    },
    computed: {
        thumbUrl: function(){
            let thumbs = this.file.thumbnails.filter( t => t.size_name === "M" );
            return thumbs.length != 0 ? thumbs[0].file_url : "";
        },
    },
}
</script>

<template>
    <div class="image-file-tile">
        <img class="image-file-tile__thumb" v-bind:src="thumbUrl" />
    </div>
</template>

<style>
.image-file-tile__thumb{
    height:100%;
    width:100%;
}
.image-file-tile{
    overflow: hidden;
}
</style>
