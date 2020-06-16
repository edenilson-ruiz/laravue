<template>
  <div class="container" style="margin-top: 50px;">
    <div class="text-center">
      <h4>File Upload with VueJS and Laravel</h4><br>
      <div style="max-width: 500px; margin: 0 auto;">
        <div v-if="success !== ''" class="alert alert-success" role="alert">
          {{ success }}
        </div>
        <form enctype="multipart/form-data" @submit="submitForm">
          <div class="input-group">
            <div class="custom-file">
              <input
                id="inputFileUpload"
                type="file"
                name="filename"
                class="custom-file-input"
                accept=" .pdf"
                @change="onFileChange"
              >
              <label class="custom-file-label" for="inputFileUpload">Choose file</label>
            </div>
            <div class="input-group-append">
              <input type="submit" class="btn btn-primary" value="Upload">
            </div>
          </div>
          <br>
          <p class="text-danger font-weight-bold">{{ filename }}</p>
        </form>
      </div>
    </div>
  </div>
</template>

<script>

import axios from 'axios';

export default {
  data() {
    return {
      filename: '',
      file: '',
      success: '',
    };
  },
  mounted() {
    console.log('Component successfully mounted.');
  },
  methods: {
    onFileChange(e) {
      // console.log(e.target.files[0]);
      this.filename = 'Selected File: ' + e.target.files[0].name;
      this.file = e.target.files[0];
    },
    submitForm(e) {
      e.preventDefault();
      const currentObj = this;
      const config = {
        headers: {
          'content-type': 'multipart/form-data',
        },
      };
      // form data
      const formData = new FormData();
      formData.append('file', this.file);

      // send upload request
      axios.post('/api/store_file', formData, config)
        .then(function(response) {
          currentObj.success = response.data.success;
          currentObj.filename = '';
        })
        .catch(function(error) {
          currentObj.output = error;
        });
    },
  },
};
</script>
