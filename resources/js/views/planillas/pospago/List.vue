<template>
  <div class="app-container">
    <div class="filter-container">
      <el-input v-model="query.keyword" :placeholder="$t('table.keyword')" style="width: 200px;" class="filter-item" @keyup.enter.native="handleFilter" />
      <el-button v-waves class="filter-item" type="primary" icon="el-icon-search" @click="handleFilter">
        {{ $t('table.search') }}
      </el-button>
      <el-button v-waves :loading="downloading" class="filter-item" type="primary" icon="el-icon-download" @click="handleDownload">
        {{ $t('table.export') }}
      </el-button>
    </div>
    <div v-if="success !== ''" class="alert alert-success" role="alert">
      {{ success }}
    </div>
    <!-- <embed :src="urlPDF" width="800px" height="2100px"> -->
    <el-table
      v-loading="loading"
      :data="list"
      border
      fit
      highlight-current-row
      stripe
      show-summary
      :summary-method="getSummaries"
    >
      <el-table-column v-if="showId" label="CodigoPlanilla" width="300">
        <template slot-scope="scope">
          <span>{{ scope.row.codigo_planilla }}</span>
        </template>
      </el-table-column>

      <el-table-column label="PeriodoPago" width="120">
        <template slot-scope="scope">
          <span>{{ scope.row.periodo_pago }}</span>
        </template>
      </el-table-column>

      <el-table-column label="Distribuidor">
        <template slot-scope="scope">
          <span>{{ scope.row.distribuidor }}</span>
        </template>
      </el-table-column>

      <el-table-column
        prop="unidad_totales"
        label="TotalUnidades"
        align="right"
        width="150"
      />

      <el-table-column
        prop="unidades_aplican"
        label="TotalAplican"
        align="right"
        width="150"
      />

      <el-table-column
        prop="comision"
        label="Comision"
        align="right"
        :formatter="currency"
        width="150"
      />

      <el-table-column class-name="status-col" label="Status" width="110">
        <template slot-scope="{row}">
          <el-tag :type="row.status | statusFilter">
            {{ row.status }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column align="center" label="Actions">
        <template slot-scope="scope">
          <div>
            <el-link rel="icon" :href="scope.row.url_file" icon="el-icon-download" target="_blank">
              Download
            </el-link>
            <el-button type="primary" size="small" icon="el-icon-upload" @click="handleEditForm(scope.row.codigo_planilla, scope.row.distribuidor);">
              Upload
            </el-button>
          </div>
        </template>
      </el-table-column>

    </el-table>

    <pagination v-show="total>0" :total="total" :page.sync="query.page" :limit.sync="query.limit" @pagination="getList" />

    <el-dialog :title="formTitle" :visible.sync="planillaFormVisible">
      <div class="form-container">
        <el-form
          ref="planillaPospagoForm"
          :model="currentPlanilla"
          label-position="left"
          label-width="150px"
          style="max-width: 500px;"
          enctype="multipart/form-data"
        >

          <el-form-item label="ID" prop="codigo_planilla">
            <el-input v-model="currentPlanilla.codigo_planilla" />
          </el-form-item>

          <el-form-item label="Periodo Pago" prop="periodo_pago">
            <el-input v-model="currentPlanilla.periodo_pago" />
          </el-form-item>

          <el-form-item label="Distribuidor" prop="distribuidor">
            <el-input v-model="currentPlanilla.distribuidor" />
          </el-form-item>

          <el-form-item label="Total Comision" prop="comision">
            <el-input v-model="currentPlanilla.comision" value="currentPlanilla.comision | currency" />
          </el-form-item>

          <el-form-item label="CargarCCF">
            <input
              id="inputFileUpload"
              class="upload-demo"
              type="file"
              name="filename"
              accept=" .pdf"
              @change="onFileChange"
            >
            <label class="el-button el-button--primary" for="inputFileUpload">
              <i class="el-icon-upload" /> {{ showLabelInput }}
            </label>
          </el-form-item>

        </el-form>
        <div slot="footer" class="dialog-footer">
          <el-button @click="planillaFormVisible = false">
            Cancel
          </el-button>
          <el-button type="primary" @click="handleSubmit()">
            Confirm
          </el-button>
        </div>
      </div>
    </el-dialog>

  </div>
</template>

<script>
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import waves from '@/directive/waves'; // Waves directive
import permission from '@/directive/permission'; // Waves directive
import axios from 'axios';

const planillaPospagoMasivoResource = new Resource('planillaPospagoMasivo');

export default {
  name: 'PospagoMasivo',
  components: { Pagination },
  directives: { waves, permission },
  filters: {
    statusFilter(status) {
      const statusMap = {
        presentada: 'success',
        pendiente: 'danger',
      };
      return statusMap[status];
    },
  },
  data() {
    return {
      file: '',
      filename: 'Seleccione un archivo pdf',
      success: '',
      list: [],
      showId: true,
      loading: true,
      downloading: false,
      total: 0,
      fileList: [],
      uploadFile: null,
      fileContent: null,
      planillaFormVisible: false,
      currentPlanilla: [],
      formTitle: '',
      query: {
        page: 1,
        limit: 10,
        keyword: '',
        role: '',
      },
    };
  },
  computed: {
    totalComision: function() {
      let sum = 0;
      this.list.forEach(function(item) {
        sum += parseFloat(item.comision);
      });

      return sum;
    },
    imageUrl() {
      return this.value;
    },
    showLabelInput() {
      return this.filename;
    },
  },
  created() {
    this.getList();
  },
  methods: {
    onFileChange(e) {
      // console.log(e.target.files[0]);
      // this.filename = 'Selected File: ' + e.target.files[0].name;
      // this.file = e.target.files[0];
      // this.file = file;
      this.file = e.target.files[0];
      this.filename = 'Archivo seleccionado: ' + e.target.files[0].name;
    },
    async getList() {
      const { limit, page } = this.query;
      this.loading = true;
      const { data, meta } = await planillaPospagoMasivoResource.list(this.query);
      this.list = data;
      this.list.forEach((element, index) => {
        element['index'] = (page - 1) * limit + index + 1;
      });
      this.total = meta.total;
      this.loading = false;
    },
    handleFileUpload(){
      this.file = this.$refs.file.files[0];
    },
    handleFilter() {
      this.query.page = 1;
      this.getList();
    },
    handleSubmit() {
      if (this.currentPlanilla.codigo_planilla !== undefined) {
        const currentObj = this;
        const config = {
          headers: {
            'content-type': 'multipart/form-data',
          },
        };
        // form data
        const formData = new FormData();
        formData.append('file', this.file);
        formData.append('planilla', JSON.stringify(this.currentPlanilla));

        // send upload request
        axios.post('/api/store_file', formData, config)
          .then(response => {
            this.$message({
              type: 'success',
              message: 'Planilla info has been updated successfully',
              duration: 5 * 1000,
            });
            currentObj.success = response.data.success;
            currentObj.filename = '';
            this.getList();
          })
          .catch(error => {
            currentObj.output = error;
          }).finally(() => {
            this.planillaFormVisible = false;
          });
      }
    },
    getSummaries(param) {
      const { columns, data } = param;
      const sums = [];
      columns.forEach((column, index) => {
        if (index === 0) {
          sums[index] = 'Totales';
          return;
        }
        const values = data.map(item => Number(item[column.property]));
        if (!values.every(value => isNaN(value))) {
          sums[index] = values.reduce((prev, curr) => {
            const value = Number(curr);
            if (!isNaN(value)) {
              return prev + curr;
            } else {
              return prev;
            }
          }, 0);
        } else {
          sums[index] = '';
        }
      });

      return sums;
    },
    currency: function(row, column) {
      var value = row[column.property];
      if (value === undefined) {
        return '';
      }

      return Number(value).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    },
    handleEditForm(id) {
      this.formTitle = 'Subir PDF de CCF';
      this.filename = 'Seleccione un archivo pdf';
      this.currentPlanilla = this.list.find(planilla => planilla.codigo_planilla === id);
      this.planillaFormVisible = true;
    },
    handleDownload() {
      this.downloading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const tHeader = ['Id', 'CodigoPlanilla', 'PeriodoPago', 'Distribuidor', 'EstadoPlanilla', 'TotalUnidades', 'TotalAplican', 'Comision'];
        const filterVal = ['index', 'codigo_planilla', 'periodo_pago', 'distribuidor', 'status', 'unidad_totales', 'unidades_aplican', 'comision'];
        const data = this.formatJson(filterVal, this.list);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'Planilla-Pospago',
          autoWidth: true, // Optional
          bookType: 'xlsx', // Optional
        });
        this.downloading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v => filterVal.map(j => {
        if (isNaN(v[j])) {
          return v[j];
        } else {
          return v[j] * 1;
        }
      }));
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handleImport(e) {
      this.file = e.target.files[0];
      // this.uploadFile = file;
      /*
      const reader = new FileReader();
      reader.readAsText(this.uploadFile.raw);
      reader.onload = async(e) => {
        try {
          this.fileContent = JSON.parse(e.target.result);
        } catch (err) {
          console.log(`Load JSON file error: ${err.message}`);
        }
      };
      */
    },
    handlePreview() {
      const myWindow = window.open();
      myWindow.document.write(JSON.stringify(this.fileContent));
      myWindow.document.close();
    },
    handleExceed(files, fileList) {
      this.$message.warning(`The limit is 1, you selected ${files.length + fileList.length} totally, please first remove the unwanted file`);
    },
    beforeRemove(file, fileList) {
      return this.$confirm(`Cancel the transfert of ${file.name} ?`);
    },
  },
};
</script>
<style>
input[type='file']#inputFileUpload {
  width: 0.1px;
  height: 0.1px;
  opacity: 0;
  overflow: hidden;
  position: absolute;
  z-index: -1;
 }
 /*
 label[for='inputFileUpload'] {
  font-size: 12px;
  font-weight: 600;
  color: #FFFFFF;
  background-color: #1890ff;
  border-color: #1890ff;
  display: inline-block;
  transition: all .5s;
  cursor: pointer;
  padding: 5px 10px !important;
  width: fit-content;
  text-align: center;
  border-radius: 5%;
 }
 */
</style>
