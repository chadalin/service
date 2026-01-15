<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Добавляем колонки в таблицу documents
        Schema::table('documents', function (Blueprint $table) {
            // Добавляем все колонки, которые еще не существуют
            $columns = [
                'embedding' => ['type' => 'json', 'nullable' => true, 'after' => 'content_text'],
                'search_indexed' => ['type' => 'boolean', 'default' => false, 'after' => 'embedding'],
                'is_parsed' => ['type' => 'boolean', 'default' => false, 'after' => 'search_indexed'],
                'parsing_quality' => ['type' => 'decimal', 'precision' => 3, 'scale' => 2, 'nullable' => true, 'after' => 'is_parsed'],
                'detected_section' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'parsing_quality'],
                'detected_system' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'detected_section'],
                'detected_component' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'detected_system'],
                'search_count' => ['type' => 'integer', 'default' => 0, 'after' => 'detected_component'],
                'view_count' => ['type' => 'integer', 'default' => 0, 'after' => 'search_count'],
                'average_relevance' => ['type' => 'decimal', 'precision' => 3, 'scale' => 2, 'nullable' => true, 'after' => 'view_count'],
            ];
            
            foreach ($columns as $columnName => $columnConfig) {
                if (!Schema::hasColumn('documents', $columnName)) {
                    $method = $columnConfig['type'];
                    $table->$method($columnName, $columnConfig['length'] ?? null);
                    
                    if (isset($columnConfig['nullable']) && $columnConfig['nullable']) {
                        $table->nullable();
                    }
                    
                    if (isset($columnConfig['default'])) {
                        $table->default($columnConfig['default']);
                    }
                }
            }
        });

        // Создаем индексы (кроме FULLTEXT - его создадим отдельно)
        Schema::table('documents', function (Blueprint $table) {
            // Проверяем и создаем только если нет
            try {
                DB::select("SHOW INDEX FROM documents WHERE Key_name = 'doc_model_category_idx'");
            } catch (\Exception $e) {
                $table->index(['car_model_id', 'category_id', 'status'], 'doc_model_category_idx');
            }
            
            try {
                DB::select("SHOW INDEX FROM documents WHERE Key_name = 'doc_search_status_idx'");
            } catch (\Exception $e) {
                $table->index(['search_indexed', 'status'], 'doc_search_status_idx');
            }
            
            try {
                DB::select("SHOW INDEX FROM documents WHERE Key_name = 'doc_section_system_idx'");
            } catch (\Exception $e) {
                $table->index(['detected_section', 'detected_system'], 'doc_section_system_idx');
            }
            
            try {
                DB::select("SHOW INDEX FROM documents WHERE Key_name = 'doc_popularity_idx'");
            } catch (\Exception $e) {
                $table->index(['search_count', 'average_relevance'], 'doc_popularity_idx');
            }
        });

        // Для полнотекстового поиска - альтернативное решение
        // 1. Создаем текстовую копию JSON поля keywords
        if (!Schema::hasColumn('documents', 'keywords_text')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->text('keywords_text')->nullable()->after('keywords');
            });
            
            // Копируем данные из JSON в текстовое поле
            DB::statement("
                UPDATE documents 
                SET keywords_text = 
                    CASE 
                        WHEN keywords IS NULL THEN NULL
                        WHEN JSON_VALID(keywords) THEN 
                            REPLACE(REPLACE(REPLACE(JSON_EXTRACT(keywords, '$[*]'), '\"', ''), '[', ''), ']', '')
                        ELSE keywords
                    END
            ");
        }

        // 2. Создаем FULLTEXT индекс на текстовых полях
        try {
            DB::select("SHOW INDEX FROM documents WHERE Key_name = 'doc_fulltext_idx'");
        } catch (\Exception $e) {
            // Создаем триггер для обновления keywords_text при изменении keywords
            DB::statement("
                CREATE TRIGGER update_keywords_text 
                BEFORE UPDATE ON documents
                FOR EACH ROW
                BEGIN
                    IF NEW.keywords IS NOT NULL AND JSON_VALID(NEW.keywords) THEN
                        SET NEW.keywords_text = REPLACE(REPLACE(REPLACE(JSON_EXTRACT(NEW.keywords, '$[*]'), '\"', ''), '[', ''), ']', '');
                    ELSE
                        SET NEW.keywords_text = NEW.keywords;
                    END IF;
                END
            ");
            
            // Создаем FULLTEXT индекс
            DB::statement("ALTER TABLE documents ADD FULLTEXT doc_fulltext_idx (content_text, title, keywords_text)");
        }

        // Создаем вспомогательные таблицы
        if (!Schema::hasTable('document_ngrams')) {
            Schema::create('document_ngrams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
                $table->string('ngram', 100);
                $table->string('ngram_type', 20)->default('trigram');
                $table->integer('position')->default(0);
                $table->integer('frequency')->default(1);
                $table->index(['ngram', 'document_id']);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('search_terms')) {
            Schema::create('search_terms', function (Blueprint $table) {
                $table->id();
                $table->string('term', 100);
                $table->string('term_type', 20)->default('synonym');
                $table->json('related_terms')->nullable();
                $table->integer('usage_count')->default(0);
                $table->decimal('relevance_weight', 3, 2)->default(1.0);
                $table->unique(['term', 'term_type']);
                $table->index('term');
                $table->timestamps();
            });
            
            // Заполняем начальными данными
            $this->seedSearchTerms();
        }

        if (!Schema::hasTable('document_relevancy')) {
            Schema::create('document_relevancy', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
                $table->string('search_term', 200);
                $table->decimal('relevance_score', 5, 4);
                $table->json('match_details')->nullable();
                $table->integer('match_count')->default(0);
                $table->unique(['document_id', 'search_term']);
                $table->index(['search_term', 'relevance_score']);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('search_cache')) {
            Schema::create('search_cache', function (Blueprint $table) {
                $table->id();
                $table->string('cache_key', 255)->unique();
                $table->json('results');
                $table->integer('result_count');
                $table->timestamp('expires_at');
                $table->integer('hits')->default(0);
                $table->index('cache_key');
                $table->index('expires_at');
                $table->timestamps();
            });
        }

        // Обновляем таблицу search_queries (если существует)
        if (Schema::hasTable('search_queries')) {
            Schema::table('search_queries', function (Blueprint $table) {
                // Добавляем новые поля если их нет
                if (!Schema::hasColumn('search_queries', 'search_type')) {
                    $table->string('search_type', 50)->default('fulltext')->nullable();
                }
                
                if (!Schema::hasColumn('search_queries', 'execution_time')) {
                    $table->decimal('execution_time', 8, 4)->nullable();
                }
                
                if (!Schema::hasColumn('search_queries', 'user_ip')) {
                    $table->string('user_ip', 45)->nullable();
                }
                
                if (!Schema::hasColumn('search_queries', 'user_agent')) {
                    $table->string('user_agent', 500)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем триггер
        DB::statement("DROP TRIGGER IF EXISTS update_keywords_text");
        
        // Удаляем таблицы
        Schema::dropIfExists('search_cache');
        Schema::dropIfExists('document_relevancy');
        Schema::dropIfExists('search_terms');
        Schema::dropIfExists('document_ngrams');
        
        // Удаляем колонки из documents
        Schema::table('documents', function (Blueprint $table) {
            $columns = [
                'embedding',
                'search_indexed',
                'is_parsed',
                'parsing_quality',
                'detected_section',
                'detected_system',
                'detected_component',
                'search_count',
                'view_count',
                'average_relevance',
                'keywords_text'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('documents', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Удаляем индексы
            $table->dropIndexIfExists('doc_model_category_idx');
            $table->dropIndexIfExists('doc_search_status_idx');
            $table->dropIndexIfExists('doc_section_system_idx');
            $table->dropIndexIfExists('doc_popularity_idx');
        });
        
        // Удаляем FULLTEXT индекс
        try {
            DB::statement("ALTER TABLE documents DROP INDEX doc_fulltext_idx");
        } catch (\Exception $e) {
            // Игнорируем ошибку если индекс не существует
        }
        
        // Удаляем колонки из search_queries
        if (Schema::hasTable('search_queries')) {
            Schema::table('search_queries', function (Blueprint $table) {
                $columns = ['search_type', 'execution_time', 'user_ip', 'user_agent'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('search_queries', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function seedSearchTerms(): void
    {
        $terms = [
            ['term' => 'двигатель', 'term_type' => 'synonym'],
            ['term' => 'мотор', 'term_type' => 'synonym'],
            ['term' => 'engine', 'term_type' => 'synonym'],
            ['term' => 'тормоз', 'term_type' => 'synonym'],
            ['term' => 'brake', 'term_type' => 'synonym'],
            ['term' => 'abs', 'term_type' => 'abbreviation'],
            ['term' => 'не заводится', 'term_type' => 'problem'],
            ['term' => 'стучит', 'term_type' => 'problem'],
            ['term' => 'перегрев', 'term_type' => 'problem'],
            ['term' => 'масло', 'term_type' => 'component'],
            ['term' => 'трансмиссия', 'term_type' => 'system'],
            ['term' => 'подвеска', 'term_type' => 'system'],
            ['term' => 'электрика', 'term_type' => 'system'],
            ['term' => 'генератор', 'term_type' => 'component'],
            ['term' => 'стартер', 'term_type' => 'component'],
            ['term' => 'аккумулятор', 'term_type' => 'component'],
        ];

        foreach ($terms as $term) {
            try {
                DB::table('search_terms')->insert([
                    'term' => $term['term'],
                    'term_type' => $term['term_type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Игнорируем дубликаты
            }
        }
    }
};