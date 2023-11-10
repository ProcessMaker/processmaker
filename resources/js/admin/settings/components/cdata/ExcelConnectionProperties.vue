<template>
  <div>
    <b-form-group
      :label="$t('Connection Type')"
      :description="
        formDescription(
          'Specifies the service, server, or protocol for storing and retrieving Microsoft Excel files.',
          'connection_type',
          errors
        )
      "
      :invalid-feedback="errorMessage('connection_type', errors)"
      :state="errorState('connection_type', errors)"
    >
      <b-form-select
        v-model="config.connection_type"
        name="connection_type"
        :options="connectionOptions.oauth"
        :state="errorState('connection_type', errors)"
      />
    </b-form-group>

    <b-form-group
      :label="$t('URI')"
      :description="
        formDescription(
          'The Uniform Resource Identifier (URI) for the Excel resource location.',
          'uri',
          errors
        )
      "
      :invalid-feedback="errorMessage('uri', errors)"
      :state="errorState('uri', errors)"
    >
      <b-form-input
        v-model="config.uri"
        autocomplete="off"
        :state="errorState('uri', errors)"
        name="uri"
      />
    </b-form-group>

    <b-form-group
      v-if="config.connection_type === 'Dropbox'"
      :label="$t('Access Token')"
      :description="
        formDescription(
          'The access token can be used to access your account via the API.',
          'o_auth_access_token',
          errors
        )
      "
      :invalid-feedback="errorMessage('o_auth_access_token', errors)"
      :state="errorState('o_auth_access_token', errors)"
    >
      <b-form-input
        v-model="config.o_auth_access_token"
        autocomplete="off"
        name="o_auth_access_token"
        :state="errorState('o_auth_access_token', errors)"
      />
    </b-form-group>
  </div>
</template>

<script>
// eslint-disable-next-line import/no-unresolved
import { FormErrorsMixin } from "SharedComponents";

export default {
  mixins: [FormErrorsMixin],
  props: {
    formData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      config: {
        connection_type: "",
        uri: "",
        o_auth_access_token: "",
        AuthScheme: "OAuth",
      },
      connectionOptions: {
        oauth: [
          { text: "Amazon S3", value: "Amazon S3" },
          { text: "Azure Blob Storage", value: "Azure Blob Storage" },
          { text: "Box", value: "Box" },
          { text: "Dropbox", value: "Dropbox" },
          { text: "Google Cloud Storage", value: "Google Cloud Storage" },
          { text: "Google Drive", value: "Google Drive" },
          { text: "OneDrive", value: "OneDrive" },
          { text: "SharePoint REST", value: "SharePoint REST" },
        ],
        others: [
          { text: "Auto", value: "Auto" },
          { text: "Local", value: "Local" },
          { text: "Azure Data Lake Storage Gen1", value: "Azure Data Lake Storage Gen1" },
          { text: "Azure Data Lake Storage Gen2", value: "Azure Data Lake Storage Gen2" },
          {
            text: "Azure Data Lake Storage Gen2 SSL",
            value: "Azure Data Lake Storage Gen2 SSL",
          },
          { text: "Azure Files", value: "Azure Files" },
          { text: "FTP", value: "FTP" },
          { text: "FTPS", value: "FTPS" },
          { text: "HDFS", value: "HDFS" },
          { text: "HDFS Secure", value: "HDFS Secure" },
          { text: "HTTP", value: "HTTP" },
          { text: "HTTPS", value: "HTTPS" },
          { text: "IBM Object Storage Source", value: "IBM Object Storage Source" },
          { text: "Oracle Cloud Storage", value: "Oracle Cloud Storage" },
          { text: "SFTP", value: "SFTP" },
          { text: "SharePoint SOAP", value: "SharePoint SOAP" },
        ],
      },
      errors: {},
    };
  },
  watch: {
    config: {
      handler() {
        this.$emit("updateFormData", this.config);
      },
      deep: true,
    },
  },
  mounted() {
    this.config.connection_type = this.formData?.connection_type ?? "";
    this.config.uri = this.formData?.uri ?? "";
    this.config.o_auth_access_token = this.formData?.o_auth_access_token ?? "";
  },
};
</script>
