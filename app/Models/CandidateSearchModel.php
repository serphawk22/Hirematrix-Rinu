<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidateSearchModel extends Model
{
    protected $table = 'candidate_profiles';

    /**
     * Main ResDex-style search.
     *
     * $filters keys (all optional):
     *   keywords            -> string, the single "Keywords" box
     *   boolean_on          -> bool, when true `keywords` is parsed as AND/OR/NOT/"phrase"
     *   mandatory           -> bool, "Mark all keywords as mandatory" (simple mode: AND vs OR)
     *   keyword_exclude     -> string, comma/space separated
     *   it_skills           -> string, comma separated ("+Add IT Skills")
     *   location            -> string
     *   exp_min, exp_max    -> years (int)
     *   salary_min, salary_max -> numbers (candidate's expected salary)
     *   notice_period       -> string
     *   employment_type     -> string
     *   education           -> string, matches education.degree / field_of_study
     *   must_have_skills[]  -> array of skill names, ALL must match
     *   gender              -> string
     *   page, per_page      -> pagination
     */
    public function search(array $filters): array
    {
        $db = \Config\Database::connect();

        $page    = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? 20));
        $offset  = ($page - 1) * $perPage;

        $where = [
            "u.role = 'candidate'",
            "cp.allow_public_recruiter_visibility = 1",
        ];
        $binds = [];

        // ---- Keywords: Boolean mode (AND/OR/NOT/"phrase") or simple Any/Mandatory mode ----
        if (!empty($filters['keywords'])) {
            if (!empty($filters['boolean_on'])) {
                $tokens = $this->parseBoolean($filters['keywords']);
                $sql    = $this->booleanToSql($tokens, $binds, 'bq');
                if ($sql !== '') $where[] = $sql;
            } else {
                $terms  = $this->splitTerms($filters['keywords']);
                $clause = $this->keywordClause($terms, !empty($filters['mandatory']), $binds, 'kw');
                if ($clause !== '') $where[] = $clause;
            }
        }

        // ---- Exclude keywords ----
        if (!empty($filters['keyword_exclude'])) {
            $terms = $this->splitTerms($filters['keyword_exclude']);
            foreach ($terms as $i => $term) {
                $key = "kw_ex_$i";
                $where[] = "NOT (cp.key_skills LIKE :{$key}: OR cp.headline LIKE :{$key}:)";
                $binds[$key] = "%{$term}%";
            }
        }

        // ---- IT Skills ("+Add IT Skills") — ANY match against candidate_skills ----
        if (!empty($filters['it_skills'])) {
            $terms = $this->splitTerms($filters['it_skills']);
            $group = [];
            foreach ($terms as $i => $skill) {
                $key = "itskill_$i";
                $group[] = "cs.skill_name LIKE :{$key}:";
                $binds[$key] = "%{$skill}%";
            }
            if ($group) {
                $where[] = "EXISTS (SELECT 1 FROM candidate_skills cs WHERE cs.candidate_id = cp.user_id
                            AND (" . implode(' OR ', $group) . "))";
            }
        }

        // ---- Location ----
        if (!empty($filters['location'])) {
            $where[] = "cp.location LIKE :location:";
            $binds['location'] = "%{$filters['location']}%";
        }

        // ---- Experience range (uses cached total_experience_months) ----
        if (!empty($filters['exp_min'])) {
            $where[] = "cp.total_experience_months >= :exp_min:";
            $binds['exp_min'] = ((int) $filters['exp_min']) * 12;
        }
        if (!empty($filters['exp_max'])) {
            $where[] = "cp.total_experience_months <= :exp_max:";
            $binds['exp_max'] = ((int) $filters['exp_max']) * 12;
        }

        // ---- Salary range ----
        if (!empty($filters['salary_min'])) {
            $where[] = "cp.expected_salary >= :salary_min:";
            $binds['salary_min'] = (float) $filters['salary_min'];
        }
        if (!empty($filters['salary_max'])) {
            $where[] = "cp.expected_salary <= :salary_max:";
            $binds['salary_max'] = (float) $filters['salary_max'];
        }

        // ---- Notice period / employment type / gender ----
        if (!empty($filters['notice_period'])) {
            $where[] = "cp.notice_period = :notice_period:";
            $binds['notice_period'] = $filters['notice_period'];
        }
        if (!empty($filters['employment_type'])) {
            $where[] = "cp.preferred_employment_type = :employment_type:";
            $binds['employment_type'] = $filters['employment_type'];
        }
        if (!empty($filters['gender'])) {
            $where[] = "cp.gender = :gender:";
            $binds['gender'] = $filters['gender'];
        }

        // ---- Education ----
        if (!empty($filters['education'])) {
            $where[] = "EXISTS (SELECT 1 FROM education e WHERE e.user_id = cp.user_id
                        AND (e.degree LIKE :edu: OR e.field_of_study LIKE :edu:))";
            $binds['edu'] = "%{$filters['education']}%";
        }

        // ---- Must-have skills (ALL must match — Resdex's star/must-have feature) ----
        if (!empty($filters['must_have_skills']) && is_array($filters['must_have_skills'])) {
            foreach (array_values($filters['must_have_skills']) as $i => $skill) {
                $key = "skill_must_$i";
                $where[] = "EXISTS (SELECT 1 FROM candidate_skills cs WHERE cs.candidate_id = cp.user_id
                            AND cs.skill_name LIKE :{$key}:)";
                $binds[$key] = "%{$skill}%";
            }
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS total
                      FROM candidate_profiles cp
                      JOIN users u ON u.id = cp.user_id
                      WHERE {$whereSql}";

        $dataSql = "SELECT cp.user_id, cp.headline, cp.location, cp.key_skills,
                           cp.expected_salary, cp.notice_period, cp.total_experience_months,
                           cp.resume_path, cp.profile_photo, cp.updated_at,
                           u.name, u.email, u.phone
                    FROM candidate_profiles cp
                    JOIN users u ON u.id = cp.user_id
                    WHERE {$whereSql}
                    ORDER BY cp.updated_at DESC
                    LIMIT {$perPage} OFFSET {$offset}";

        $total = (int) ($db->query($countSql, $binds)->getRow()->total ?? 0);
        $rows  = $db->query($dataSql, $binds)->getResultArray();

        return [
            'results'     => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /** Split a comma/space separated keyword string into clean terms. */
    private function splitTerms(string $raw): array
    {
        $parts = preg_split('/[,]+/', $raw);
        $parts = array_map('trim', $parts);
        return array_filter($parts, fn($p) => $p !== '');
    }

    /** Build an AND-joined (mandatory) or OR-joined (any) keyword clause. */
    private function keywordClause(array $terms, bool $joinAnd, array &$binds, string $prefix): string
    {
        $clauses = [];
        foreach (array_values($terms) as $i => $term) {
            $key = "{$prefix}_{$i}";
            $clauses[] = "(u.name LIKE :{$key}: OR cp.key_skills LIKE :{$key}: OR cp.headline LIKE :{$key}: OR cp.preferred_job_titles LIKE :{$key}:)";
            $binds[$key] = "%{$term}%";
        }
        if (empty($clauses)) return '';
        return $joinAnd ? implode(' AND ', $clauses) : '(' . implode(' OR ', $clauses) . ')';
    }

    /**
     * Parse a Boolean keyword string ("Java AND Spring OR Hibernate NOT PHP",
     * quoted "exact phrases" supported) into an ordered token list.
     */
    private function parseBoolean(string $query): array
    {
        $parts = preg_split('/\s+(AND|OR|NOT)\s+/i', trim($query), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $tokens = [];
        $op     = 'AND';

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;

            if (in_array(strtoupper($part), ['AND', 'OR', 'NOT'], true)) {
                $op = strtoupper($part);
                continue;
            }

            $term = trim($part, "\"' ");
            if ($term === '') continue;

            $tokens[] = ['op' => $op, 'term' => $term];
            $op = 'AND'; // default relation resets after each term
        }

        return $tokens;
    }

    /** Turn parsed Boolean tokens into a single SQL WHERE fragment. */
    private function booleanToSql(array $tokens, array &$binds, string $prefix): string
    {
        if (empty($tokens)) return '';

        $andGroups      = [];
        $currentOrGroup = [];

        foreach ($tokens as $idx => $tok) {
            $key    = "{$prefix}_{$idx}";
            $clause = "(u.name LIKE :{$key}: OR cp.key_skills LIKE :{$key}: OR cp.headline LIKE :{$key}: OR cp.preferred_job_titles LIKE :{$key}:)";
            $binds[$key] = "%{$tok['term']}%";

            if ($tok['op'] === 'NOT') {
                if ($currentOrGroup) {
                    $andGroups[] = '(' . implode(' OR ', $currentOrGroup) . ')';
                    $currentOrGroup = [];
                }
                $andGroups[] = "NOT {$clause}";
            } elseif ($tok['op'] === 'OR') {
                $currentOrGroup[] = $clause;
            } else { // AND
                if ($currentOrGroup) {
                    $andGroups[] = '(' . implode(' OR ', $currentOrGroup) . ')';
                    $currentOrGroup = [];
                }
                $currentOrGroup[] = $clause;
            }
        }

        if ($currentOrGroup) {
            $andGroups[] = '(' . implode(' OR ', $currentOrGroup) . ')';
        }

        return implode(' AND ', $andGroups);
    }

    /** Recompute and cache one candidate's total experience (call after work_experiences edits). */
    public function syncExperience(int $userId): void
    {
        $db = \Config\Database::connect();
        $db->query(
            "UPDATE candidate_profiles SET total_experience_months = (
                SELECT COALESCE(SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(end_date, CURDATE()))), 0)
                FROM work_experiences WHERE user_id = ?
             ) WHERE user_id = ?",
            [$userId, $userId]
        );
    }

    /** Full profile for the candidate detail view. */
    public function getFullProfile(int $userId): ?array
    {
        $db = \Config\Database::connect();

        $profile = $db->query(
            "SELECT cp.*, u.name, u.email, u.phone
             FROM candidate_profiles cp
             JOIN users u ON u.id = cp.user_id
             WHERE cp.user_id = ?",
            [$userId]
        )->getRowArray();

        if (!$profile) return null;

        $profile['skills'] = $db->query(
            "SELECT skill_name FROM candidate_skills WHERE candidate_id = ?", [$userId]
        )->getResultArray();

        $profile['experience'] = $db->query(
            "SELECT * FROM work_experiences WHERE user_id = ? ORDER BY start_date DESC", [$userId]
        )->getResultArray();

        $profile['education'] = $db->query(
            "SELECT * FROM education WHERE user_id = ? ORDER BY end_year DESC", [$userId]
        )->getResultArray();

        $profile['certifications'] = $db->query(
            "SELECT * FROM certifications WHERE user_id = ? ORDER BY issue_date DESC", [$userId]
        )->getResultArray();

        $profile['projects'] = $db->query(
            "SELECT * FROM candidate_projects WHERE user_id = ? ORDER BY start_date DESC", [$userId]
        )->getResultArray();

        return $profile;
    }

    // ================================================================
    // Recent / Saved searches ("Manage Searches" — Naukri's saved-search tabs)
    // ================================================================

    /** Auto-log every executed search as a "recent search" (is_manual = 0). */
    public function logSearch(int $recruiterId, array $filters): void
    {
        $db = \Config\Database::connect();

        $db->table('resdex_saved_searches')->insert([
            'recruiter_id'    => $recruiterId,
            'search_name'     => $this->buildSearchLabel($filters),
            'filters_json'    => json_encode($filters),
            'alert_frequency' => 'none',
            'is_manual'       => 0,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        // keep recent-search history bounded to the latest 50 per recruiter
        $ids = $db->table('resdex_saved_searches')
            ->select('id')
            ->where('recruiter_id', $recruiterId)
            ->where('is_manual', 0)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        if (count($ids) > 50) {
            $toDelete = array_slice(array_column($ids, 'id'), 50);
            if ($toDelete) {
                $db->table('resdex_saved_searches')->whereIn('id', $toDelete)->delete();
            }
        }
    }

    /** Build a human-readable label for a search, e.g. "React, Node | Pune | 2-5 yrs". */
   /** Build a human-readable label for a search, e.g. "React, Node | Pune | 2-5 yrs". */
public function buildSearchLabel(array $filters): string
{
    $bits = [];
    if (!empty($filters['keywords']))  $bits[] = $filters['keywords'];
    if (!empty($filters['location']))  $bits[] = $filters['location'];
    if (!empty($filters['exp_min']) || !empty($filters['exp_max'])) {
        $bits[] = trim(($filters['exp_min'] ?? '0') . '-' . ($filters['exp_max'] ?? '') . ' yrs', '-');
    }
    return $bits ? implode(' | ', $bits) : 'Untitled search';
}

    /** Fetch saved (is_manual = 1) or recent (is_manual = 0) searches for a recruiter. */
    public function getSearches(int $recruiterId, bool $manual, int $limit = 100): array
    {
        $db = \Config\Database::connect();
        return $db->table('resdex_saved_searches')
            ->where('recruiter_id', $recruiterId)
            ->where('is_manual', $manual ? 1 : 0)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /** Delete a saved/recent search, scoped to its owner. */
    public function deleteSearch(int $id, int $recruiterId): bool
    {
        $db = \Config\Database::connect();
        return (bool) $db->table('resdex_saved_searches')
            ->where('id', $id)
            ->where('recruiter_id', $recruiterId)
            ->delete();
    }
/** Delete saved/recent searches (one or many), scoped to owner. Returns the ids actually deleted. */
public function deleteSearches(array $ids, int $recruiterId): array
{
    $db  = \Config\Database::connect();
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

    if (empty($ids)) return [];

    // Only delete rows that actually belong to this recruiter — never trust
    // client-submitted ids alone, same guard used for folders.
    $owned = $db->table('resdex_saved_searches')
        ->select('id')
        ->where('recruiter_id', $recruiterId)
        ->whereIn('id', $ids)
        ->get()->getResultArray();

    $ownedIds = array_map(fn($row) => (int) $row['id'], $owned);

    if (!empty($ownedIds)) {
        $db->table('resdex_saved_searches')->whereIn('id', $ownedIds)->delete();
    }

    return $ownedIds;
}
    // ================================================================
    // Folders
    // ================================================================

    /** Candidates saved inside one recruiter folder (with ownership check). */
    public function getFolderCandidates(int $folderId, int $recruiterId): ?array
    {
        $db = \Config\Database::connect();

        $folder = $db->table('resdex_folders')
            ->where('id', $folderId)
            ->where('recruiter_id', $recruiterId)
            ->get()->getRowArray();

        if (!$folder) return null;

        $candidates = $db->query(
            "SELECT cp.user_id, cp.headline, cp.location, cp.key_skills, cp.total_experience_months,
                    cp.notice_period, cp.resume_path, u.name, fc.added_at
             FROM resdex_folder_candidates fc
             JOIN candidate_profiles cp ON cp.user_id = fc.candidate_id
             JOIN users u ON u.id = cp.user_id
             WHERE fc.folder_id = ?
             ORDER BY fc.added_at DESC",
            [$folderId]
        )->getResultArray();

        return ['folder' => $folder, 'candidates' => $candidates];
    }

    /** Remove a candidate from a folder, scoped to owner. */
    public function removeFromFolder(int $folderId, int $candidateId, int $recruiterId): bool
    {
        $db = \Config\Database::connect();

        $owns = $db->table('resdex_folders')
            ->where('id', $folderId)
            ->where('recruiter_id', $recruiterId)
            ->countAllResults();

        if (!$owns) return false;

        return (bool) $db->table('resdex_folder_candidates')
            ->where('folder_id', $folderId)
            ->where('candidate_id', $candidateId)
            ->delete();
    }

    public function hashFilters(array $filters): string
{
    // Drop pagination + volatile keys that shouldn't affect "is this the same search"
    unset($filters['page'], $filters['per_page']);

    // Normalize: sort keys, cast arrays to sorted comma strings, drop empty values
    $clean = [];
    foreach ($filters as $key => $val) {
        if (is_array($val)) {
            $val = implode(',', array_filter(array_map('trim', $val)));
        }
        $val = trim((string) $val);
        if ($val === '' || $val === '0' && $key !== 'mandatory' && $key !== 'boolean_mode') {
            continue;
        }
        $clean[$key] = $val;
    }
    ksort($clean);

    return md5(json_encode($clean));
}

/** Look up an existing manually-saved search by its filter hash. */
public function findSavedSearchByHash(int $recruiterId, string $hash): ?array
{
    $db = \Config\Database::connect();
    return $db->table('resdex_saved_searches')
        ->where('recruiter_id', $recruiterId)
        ->where('filters_hash', $hash)
        ->where('is_manual', 1)
        ->get()->getRowArray() ?: null;
}

}
