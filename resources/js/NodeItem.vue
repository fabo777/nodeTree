<template>
    <li>
        {{ node.title }} - {{ "ID:" + node.id }} -
        {{ "Order:" + node.ordering }}
        <button @click="openAddNodeModal(node)">Add Child</button>
        <button @click="editNode(node)">Edit</button>
        <button @click="deleteNode(node.id)">Delete</button>
        <button @click="moveNode(node)">Move</button>
        <button v-if="node.parent_node_id" @click="reorderNode(node)">
            Reorder
        </button>

        <ul v-if="node.children && node.children.length > 0">
            <NodeItem
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                @openAddNodeModal="openAddNodeModal"
                @editNode="editNode"
                @deleteNode="deleteNode"
                @moveNode="moveNode"
                @reorderNode="reorderNode"
            />
        </ul>
    </li>
</template>

<script>
export default {
    props: {
        node: {
            type: Object,
            required: true,
        },
    },
    methods: {
        editNode(node) {
            this.$emit("editNode", node);
        },
        deleteNode(id) {
            this.$emit("deleteNode", id);
        },
        moveNode(node) {
            this.$emit("moveNode", node);
        },
        openAddNodeModal(node) {
            this.$emit("openAddNodeModal", node);
        },
        async reorderNode(node) {
            this.$emit("reorderNode", node);
        },
    },
};
</script>

<style>
button {
    margin: 2px;
}
</style>
