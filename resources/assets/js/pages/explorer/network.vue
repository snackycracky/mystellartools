<template>
    <v-layout row wrap>
        <v-flex xs12>
            <div class="headline">
                Stellar Network
                <v-btn loading v-if="loading" flat class="blue--text my-0 mx-0" small></v-btn>
            </div>
        </v-flex>
        <v-flex sm12 md8>
            <div class="caption grey--text mb-4 mt-4">MAP OF STELLAR-CORE NODES</div>
        </v-flex>
        <v-flex sm12 md4>
            <div class="caption grey--text mb-4 mt-4 pointer"
                 @click="infoVisible = !infoVisible"
                 v-tooltip:left="{ html: 'Toggle information panel' }"
            >
                INFORMATION
                <v-icon class="pl-2 grey--text caption">remove_red_eye</v-icon>
            </div>
        </v-flex>
        <v-flex sm12 :class="{md8: infoVisible}">
            <template v-if="!loading">
                <span v-if="nodes.length === 0" class="grey--text">
                    Node watching the network is currently offline.
                </span>
                <div v-else ref="network"></div>
            </template>
        </v-flex>
        <v-flex sm12 md4 v-if="infoVisible">
            <div>
                <a href="#" @click="helpDialog = true">Add your node</a>

                <table class="mt-4 mb-4 map-legend" cellpadding="0">
                    <tr>
                        <td><v-icon class="green--text">fiber_manual_record</v-icon></td>
                        <td>No problems</td>
                    </tr>
                    <tr>
                        <td><v-icon class="orange--text">fiber_manual_record</v-icon></td>
                        <td>Node will fail after one more node goes missing or disagrees with it OR node is missing two or more nodes from its quorum set</td>
                    </tr>
                    <tr>
                        <td><v-icon class="red--text">fiber_manual_record</v-icon></td>
                        <td>Node is not synced with the network</td>
                    </tr>
                </table>

                <p class="grey--text">
                    Disclaimer: This map is based on the data from one stellar-core node.
                    It may not reflect the actual state of the network.
                </p>
            </div>
        </v-flex>

        <v-dialog v-model="nodeDialog" v-if="selectedNode" width="500px" lazy absolute>
            <v-card>
                <v-card-title>
                    <div class="headline" v-text="selectedNode.label ? selectedNode.label : selectedNode.public_key"></div>
                </v-card-title>
                <v-card-text>
                    <table class="first-padding">
                        <tr>
                            <td width="100"><b>Status</b></td>
                            <td
                                    :style="ledgerStatus(selectedNode.ledger).style"
                                    v-text="ledgerStatus(selectedNode.ledger).text"
                            ></td>
                        </tr>
                        <tr>
                            <td><b>IP:Port</b></td>
                            <td v-text="selectedNode.ip + ':' + selectedNode.port"></td>
                        </tr>
                        <tr>
                            <td valign="top"><b>Core</b></td>
                            <td class="break-all" v-text="selectedNode.version"></td>
                        </tr>
                        <tr>
                            <td valign="top"><b>Public key</b></td>
                            <td class="break-all" v-text="selectedNode.public_key"></td>
                        </tr>
                    </table>
                </v-card-text>
                <v-expansion-panel v-if="ledgers.length" class="mt-4 mb-4">
                    <v-expansion-panel-content v-for="ledger in ledgers" :key="ledger.id">
                        <div slot="header">
                            <v-icon :style="ledgerStatus(ledger).style">stop</v-icon>
                            <span v-text="ledger.ledger"></span>
                        </div>
                        <v-card>
                            <v-card-text class="grey lighten-3 pl-5 pr-5">
                                <table width="100%" class="left-and-right" cellspacing="0">
                                    <tr v-if="ledger.hash">
                                        <td><b>Hash</b></td>
                                        <td v-text="ledger.hash"></td>
                                    </tr>
                                    <tr v-if="ledger.phase">
                                        <td><b>Phase</b></td>
                                        <td v-text="ledger.phase"></td>
                                    </tr>
                                    <tr v-if="ledger.agree">
                                        <td><b>Agree</b></td>
                                        <td v-text="ledger.agree"></td>
                                    </tr>
                                    <tr v-if="ledger.fail_at">
                                        <td><b>Fail at</b></td>
                                        <td v-text="ledger.fail_at"></td>
                                    </tr>
                                    <tr v-if="ledger.value">
                                        <td><b>Nodes in quorum</b></td>
                                        <td v-text="ledger.value.keys().length"></td>
                                    </tr>
                                </table>
                            </v-card-text>
                            <template v-if="ledger.disagree || ledger.missing">
                                <v-divider></v-divider>
                                <v-card-text class="grey lighten-3 pl-5 pr-5">
                                    <table width="100%" class="left-and-right" cellspacing="0">
                                        <template v-if="ledger.disagree">
                                            <tr>
                                                <td class="mb-1"><b>Disagree</b></td>
                                                <td class="mb-1" v-text="ledger.disagree.length"></td>
                                            </tr>
                                            <tr v-for="pk in ledger.disagree">
                                                <td colspan="2" class="small-public-key">
                                                    <public-key :value="pk" v-if="pk.length > 5"></public-key>
                                                    <span v-else v-text="pk"></span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-if="ledger.missing">
                                            <tr>
                                                <td class="mb-1"><b>Missing</b></td>
                                                <td class="mb-1" v-text="ledger.missing.length"></td>
                                            </tr>
                                            <tr v-for="pk in ledger.missing">
                                                <td colspan="2" class="small-public-key">
                                                    <public-key :value="pk" v-if="pk.length > 5"></public-key>
                                                    <span v-else v-text="pk"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </table>
                                </v-card-text>
                            </template>
                            <template v-if="ledger.quorum">
                                <v-divider></v-divider>
                                <v-card-text class="grey lighten-3 pl-5 pr-5 qsets">
                                    <b>Quorum set</b>
                                    <qset :quorum="ledger.quorum" class="mt-1 mb-3"></qset>
                                </v-card-text>
                            </template>
                        </v-card>
                    </v-expansion-panel-content>
                </v-expansion-panel>
                <v-card-text>
                    <div class="grey--text">
                        Last updated <span v-text="selectedNode.updated_at"></span>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn loading flat class="blue--text pull-right" v-if="ledgersLoading"></v-btn>
                    <v-btn class="grey--text" flat @click.native="nodeDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="helpDialog" width="500px" lazy absolute>
            <v-card>
                <v-card-title>
                    <div class="headline">Add your node</div>
                </v-card-title>
                <v-card-text>
                    <p>
                        Add <code>173.212.239.196:11627</code> to <code>KNOWN_PEERS</code> in your stellar-core.cfg
                    </p>
                    <p>
                        If you don't want to modify your config file,
                        send us IP:PORT of your stellar-core node to contact@mystellar.tools
                    </p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="grey--text" flat @click.native="helpDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-layout>
</template>

<script>
  import axios from 'axios'
  import vis from 'vis'
  import Qset from './network/qset'
  import { nodes as knownNodes, groups as knownGroups } from '~/stellar/network_nodes'

  export default {
    metaInfo: () => ({
      title: 'Stellar Network',
    }),

    components: {
      Qset,
    },

    layout: 'explorer',

    data: (vm) => ({
      loading: true,
      infoVisible: true,
      peers: [],
      nodeDialog: false,
      selectedNode: null,
      helpDialog: false,
      ledgersLoading: false,
      ledgers: [],
    }),

    computed: {
      networkData () {
        return {
          nodes: this.nodes,
          edges: this.edges,
        }
      },

      nodes () {
        return this.peers.map(node => {
          node.color = {
            border: '#BBB',
            background: '#DDD',
            highlight: {
              border: '#BBB',
              background: '#DDD',
            },
          }

          if (node.public_key in knownNodes) {
            Object.assign(node, knownNodes[node.public_key])
          } else {
            node.label = node.public_key.substring(0, 5)
          }

          node.color.border = node.color.highlight.border = this.ledgerStatus(node.ledger).style.split(' ')[1]

          return node
        })
      },

      edges () {
        let edges = []

        this.nodes.forEach(node => {
          if (node.ledger && node.ledger.quorum) {
            this.getConnectedNodes(node.ledger.quorum.v).forEach(otherNode => {
              edges.push({
                from: node.id,
                to: otherNode.id
              })
            })
          }
        })

        return edges
      },
    },

    methods: {
      getConnectedNodes (qset) {
        let nodes = []

        qset.forEach(peer => {
          if (typeof peer === 'string') {
            let key = this.findNodeByPublicKey(peer)

            if (key) {
              nodes.push(key)
            }
          } else {
            nodes.push(...this.getConnectedNodes(peer.v))
          }
        })

        return nodes
      },

      findNodeByPublicKey (publicKey) {
        return this.nodes.find(node => node.public_key === publicKey)
      },

      selectNode(data) {
        this.selectedNode = this.peers.find(peer => peer.id === data.nodes[0])
        this.ledgersLoading = true
        this.ledgers = []

        axios.get('/api/peers/' + this.selectedNode.public_key)
          .then(response => {
            this.ledgers = response.data
          })
          .catch(() => {})
          .then(() => {
            this.ledgersLoading = false
          })

        setTimeout(() => {
          this.nodeDialog = true
        }, 1)
      },

      ledgerStatus (ledger) {
        let statuses = [
          {
            text: 'OK',
            style: 'color: #4caf50',
          },
          {
            text: 'WARN',
            style: 'color: #ff9800',
          },
          {
            text: 'PROBLEMS',
            style: 'color: #f44336',
          },
          {
            text: 'UNKNOWN',
            style: 'color: #BBB',
          }
        ]

        if (ledger === null) {
          return statuses[3]
        }

        if (ledger.phase === 'expired') {
          return statuses[2]
        }

        if ((ledger.fail_at && ledger.fail_at === 1) || (ledger.missing && ledger.missing.length > 1)) {
          return statuses[1]
        }

        return statuses[0]
      },
    },

    mounted () {
      this.options = {
        edges: {
          smooth: false,
          selfReferenceSize: 10,
          color: {
            color: '#ddd',
            inherit: false,
          },
          arrows: {
            to: {
              enabled: true,
              scaleFactor: .4,
            },
          },
        },
        nodes: {
          shape: 'dot',
          size: 10,
          borderWidth: 5,
          font: {
            size: 10,
          },
        },
        groups: knownGroups,
      }


      axios.get('/api/peers')
        .then(response => {
          this.peers = response.data

          this.loading = false

          this.$nextTick(() => {
            this.options.height = window.innerHeight + 'px'

            const network = new vis.Network(this.$refs.network, this.networkData, this.options)

            network.on('selectNode', this.selectNode)
          })
        })
    }
  }
</script>