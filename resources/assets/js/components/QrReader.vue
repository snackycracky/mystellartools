<template>
    <v-dialog v-model="qrDialog" lazy absolute width="332">
        <v-card>
            <v-card-title>
                <div class="headline">QR Code reader</div>
            </v-card-title>
            <v-card-text>
                <span class="red--text" v-if="qrError" v-text="qrError"></span>
                <video ref="video" autoplay style="display:none"></video>
                <canvas
                        ref="canvas"
                        :width="qrWidth"
                        :height="qrHeight"
                        :style="{width: qrWidth + 'px', height: qrHeight + 'px', display: qrError ? 'none' : 'block'}"
                ></canvas>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn flat @click.native="closeQrDialog">Close</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
  import { events } from '~/utils'

  const jsQR = require('jsqr')

  export default {
    data: () => ({
      qrDialog: false,
      qrError: '',
      qrWidth: 300,
      qrHeight: 300,
    }),

    watch: {
      qrDialog (opened) {
        if (opened) {
          window.setTimeout(() => {
            this.ctx = this.$refs.canvas.getContext('2d')
          }, 10)
        }
      },
    },

    methods: {
      openQrDialog () {
        this.qrDialog = true

        this.$nextTick(() => {
          if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}})
              .then(mediaStream => {
                this.$refs.video.srcObject = mediaStream

                this.mediaStream = mediaStream

                window.requestAnimationFrame(this.tick)
              })
              .catch(err => {
                this.qrError = err.message
              })
          } else {
            this.qrError = 'Your browser does not support this feature'
          }
        })
      },

      tick () {
        let decoded = null

        if (this.$refs.video.readyState === this.$refs.video.HAVE_ENOUGH_DATA) {
          this.ctx.drawImage(this.$refs.video, 0, 0, this.qrWidth, this.qrHeight)

          let imageData = this.ctx.getImageData(0, 0, this.qrWidth, this.qrHeight)

          decoded = jsQR.decodeQRFromImage(
            imageData.data,
            imageData.width,
            imageData.height
          )

          if (decoded) {
            events.$emit('qr-reader:read', decoded)

            this.closeQrDialog()
          }
        }

        if (!decoded) {
          window.requestAnimationFrame(this.tick)
        }
      },

      closeQrDialog () {
        this.qrDialog = false

        if (this.mediaStream) {
          this.mediaStream.getVideoTracks()[0].stop()
        }
      },
    },

    created () {
      events.$on('qr-reader:open', () => {
        this.openQrDialog()
      })
    },
  }
</script>