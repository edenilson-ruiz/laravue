<template>
  <div class="container">
    <div class="large-12 medium-12 small-12 cell">
      <label>Files
        <input id="file" ref="file" :v-model="file" name="file" type="file" @change="handleFileUpload()">
      </label>
      <button @click="submitFiles()">Submit</button>
    </div>
  </div>
</template>

<script>

import axios from 'axios';

export default {
  /*
  Defines the data used by the component
  */
  data(){
    return {
      file: '',
    };
  },
  methods: {
    addFiles(){
      this.$refs.files.click();
    },
    submitFiles() {
      const formData = new FormData();
      formData.append('file', this.file);
      axios.put('/api/planillaPospagoMasivo/1',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      ).then(function(){
        console.log('SUCCESS!!');
      }).catch(function(){
        console.log('FAILURE!!');
      });
    },
    handleFileUpload(){
      this.file = this.$refs.file.files[0];
    },
    removeFile(key){
      this.files.splice(key, 1);
    },
  },
};
</script>

<style>
  input[type="file"]{
    position: absolute;
    top: -500px;
  }

  div.file-listing{
    width: 200px;
  }

  span.remove-file{
    color: red;
    cursor: pointer;
    float: right;
  }
</style>
