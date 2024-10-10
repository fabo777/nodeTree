<template>
    <div>
        <h1>Node Management</h1>

        <ul>
            <NodeItem
                v-if="node"
                :node="node"
                @editNode="editNode"
                @deleteNode="deleteNode"
                @moveNode="moveNode"
                @openAddNodeModal="openAddNodeModal"
                @reorderNode="reorderNode"
            />
        </ul>

        <div v-if="showModal" class="modal">
            <div class="modal-content">
                <h2>{{ isEditMode ? "Edit Node" : "Add Node" }}</h2>
                <div>
                    <input
                        class="modal-content-div"
                        type="text"
                        v-model="nodeTitle"
                        placeholder="Node Title"
                        required
                    />
                </div>
                <div v-if="isEditMode">
                    <input
                        class="modal-content-div"
                        type="text"
                        v-model="parentNodeId"
                        placeholder="Parent Node ID (pre-filled)"
                    />
                </div>
                <div v-else>
                    <input
                        class="modal-content-div"
                        type="text"
                        v-model="parentNodeId"
                        placeholder="Parent Node ID (pre-filled)"
                        readonly
                        disabled
                    />
                </div>
                <input type="hidden" v-model="currentNodeId" />
                <button @click="saveNode">Save</button>
                <button @click="closeModal">Close</button>
            </div>
        </div>

        <div v-if="errorMessage" class="modal">
            <div class="modal-content">
                <h2>Error</h2>
                <p>{{ errorMessage }}</p>
                <button @click="clearError">Close</button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import NodeItem from "./NodeItem.vue";

export default {
    components: {
        NodeItem,
    },
    data() {
        return {
            node: {},
            showModal: false,
            isEditMode: false,
            nodeTitle: "",
            parentNodeId: "",
            currentNodeId: null,
            errorMessage: null,
        };
    },
    methods: {
        async fetchNodes() {
            try {
                const response = await axios.get("/api/tree");
                this.node = response.data;
            } catch (error) {
                this.handleError(error);
            }
        },
        openModal() {
            this.showModal = true;
            this.nodeTitle = "";
            this.parentNodeId = "";
            this.currentNodeId = null;
            this.isEditMode = false;
        },
        closeModal() {
            this.showModal = false;
        },
        async saveNode() {
            try {
                if (this.isEditMode) {
                    await axios.put(`/api/tree/${this.currentNodeId}`, {
                        title: this.nodeTitle,
                        parent_node_id: this.parentNodeId,
                    });
                } else {
                    await axios.post(`/api/tree/${this.parentNodeId || 1}`, {
                        title: this.nodeTitle,
                    });
                }
                this.fetchNodes();
                this.closeModal();
            } catch (error) {
                this.handleError(error);
            }
        },
        async editNode(node) {
            this.openModal();
            this.nodeTitle = node.title;
            this.currentNodeId = node.id;
            this.isEditMode = true;
            this.parentNodeId = node.parent_node_id;
        },
        async deleteNode(id) {
            if (confirm("Are you sure you want to delete this node?")) {
                try {
                    await axios.delete(`/api/tree/${id}`);
                    this.fetchNodes();
                } catch (error) {
                    this.handleError(error);
                }
            }
        },
        async moveNode(node) {
            const newParentId = window.prompt(
                "Enter new parent node ID (or leave blank for root):"
            );
            if (newParentId === null) {
                return;
            }
            const validId = newParentId === "" || !isNaN(newParentId);
            if (validId) {
                try {
                    await axios.put(
                        `/api/tree/${node.id}/move/${newParentId || 1}`
                    );
                    this.fetchNodes();
                } catch (error) {
                    this.handleError(error);
                }
            } else {
                alert("Invalid input. Please enter a valid parent node ID.");
            }
        },
        openAddNodeModal(node) {
            this.showModal = true;
            this.isEditMode = false;
            this.parentNodeId = node.id;
            this.nodeTitle = "";
        },
        handleError(error) {
            this.errorMessage =
                error.response?.data?.error || "An unexpected error occurred.";
        },
        clearError() {
            this.errorMessage = null;
        },
        async reorderNode(node) {
            try {
                await axios.put(`/api/tree/${node.id}/reorder`);
                this.fetchNodes();
            } catch (error) {
                this.handleError(error);
            }
        },
    },
    mounted() {
        this.fetchNodes();
    },
};
</script>

<style scoped>
.modal {
    display: flex;
    justify-content: center;
    align-items: center;
    position: fixed;
    flex-direction: row;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}
.modal-content {
    background: white;
    padding: 20px;
    border-radius: 5px;
    width: 30%;
}
.modal-content-div {
    width: 100%;
    margin-bottom: 10px;
}
</style>
