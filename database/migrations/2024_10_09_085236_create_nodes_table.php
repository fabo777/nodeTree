<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Node;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('parent_node_id')->nullable();
            $table->foreign('parent_node_id')->references('id')->on('nodes')->onDelete('cascade');
            $table->integer('ordering')->default(0);
            $table->timestamps();
        });
        if (Node::whereNull('parent_node_id')->doesntExist()) {
            Node::create([
                'title' => 'Main Node',
                'parent_node_id' => null,
                'ordering' => 0,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
