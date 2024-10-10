<?php

namespace App\Http\Controllers;

use App\Models\Node;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class NodeController extends Controller
{

    /**  Fetches the entire tree structure starting from the root node and loads its children in the correct order. */
    public function getTree()
    {
        try {
            $rootNode = Node::with(['children' => function ($query) {
                $query->orderBy('ordering');
            }])->where('parent_node_id', null)->first();

            if (!$rootNode) {
                return response()->json(['error' => 'Root node not found'], 404);
            }

            $this->loadChildren($rootNode);

            return response()->json($rootNode);
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching the tree', 'details' => $e->getMessage()], 500);
        }
    }
    /** Recursively loads the children of a given node and orders them. */
    private function loadChildren($node)
    {
        if ($node->children->isNotEmpty()) {
            foreach ($node->children as $child) {
                $child->setRelation('children', $child->children()->orderBy('ordering')->with('children')->get());
                $this->loadChildren($child);
            }
        }
    }
    /** Validates the request and adds a new node as a child of the specified parent node. */
    public function addNode(Request $request, $parentId)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
            ]);

            $parent = Node::findOrFail($parentId);

            $ordering = Node::where('parent_node_id', $parentId)->max('ordering') + 1;

            $node = Node::create([
                'title' => $request->title,
                'parent_node_id' => $parentId,
                'ordering' => $ordering,
            ]);

            return response()->json($node, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Parent node not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred while adding the node', 'details' => $e->getMessage()], 500);
        }
    }
    /** Updates the title and optionally the parent of an existing node. */
    public function updateNode(Request $request, $id)
    {
        try {
            $node = Node::findOrFail($id);

            $request->validate([
                'title' => 'required|string|max:255',
                'parent_node_id' => 'nullable|exists:nodes,id',
            ]);

            if ($node->id == 1 && $request->parent_node_id !== null) {
                return response()->json(['error' => 'Cannot change parent of root node'], 400);
            }

            if ($request->parent_node_id !== null && $request->parent_node_id != $node->parent_node_id) {
                $newParentId = $request->parent_node_id;
                $newOrdering = Node::where('parent_node_id', $newParentId)->max('ordering') + 1;

                $node->update([
                    'title' => $request->title,
                    'parent_node_id' => $newParentId,
                    'ordering' => $newOrdering,
                ]);
            } else {
                $node->update(['title' => $request->title]);
            }

            return response()->json($node);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Node not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the node', 'details' => $e->getMessage()], 500);
        }
    }
    /**  Deletes a specified node with children, ensuring the root node cannot be deleted.*/
    public function deleteNode($id)
    {
        try {
            $node = Node::findOrFail($id);

            if ($node->id == 1) {
                return response()->json(['error' => 'Cannot delete root node'], 400);
            }

            $node->delete();

            return response()->json(['success' => true]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Node not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred while deleting the node', 'details' => $e->getMessage()], 500);
        }
    }
    /** Moves a specified node under a new parent node. */
    public function moveNode(Request $request, $id, $newParentId)
    {
        try {
            $node = Node::findOrFail($id);
            $newParent = Node::findOrFail($newParentId);

            if ($node->id == 1) {
                return response()->json(['error' => 'Cannot move root node'], 400);
            }

            $node->update([
                'parent_node_id' => $newParentId,
                'ordering' => Node::where('parent_node_id', $newParentId)->max('ordering') + 1,
            ]);

            return response()->json($node);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Node or new parent node not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred while moving the node', 'details' => $e->getMessage()], 500);
        }
    }
    /** Reorders a specified node to the first position among its siblings and adjusts the order of other siblings accordingly. */
    public function reorderNode(Request $request, $nodeId)
    {
        try {

            $node = Node::findOrFail($nodeId);
            $parentId = $node->parent_node_id;


            $node->ordering = 1;
            $node->save();


            $siblings = Node::where('parent_node_id', $parentId)
                ->where('id', '!=', $nodeId)
                ->orderBy('ordering')
                ->get();

            foreach ($siblings as $index => $sibling) {
                $sibling->ordering = $index + 2;
                $sibling->save();
            }

            return response()->json(['success' => true], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Node not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }
}
