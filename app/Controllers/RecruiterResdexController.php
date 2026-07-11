<?php

namespace App\Controllers;

use App\Models\CandidateSearchModel;

class RecruiterResdexController extends BaseController
{
    protected CandidateSearchModel $candidateSearch;

    public function __construct()
    {
        $this->candidateSearch = new CandidateSearchModel();
    }

/** GET /recruiter/resdex — search form + results (results empty until a search is run) */
/** GET /recruiter/resdex — search form + results (results empty until a search is run) */
public function index()
{
    $filters     = $this->collectFilters('get');
    $hasSearched = $this->request->getGet('search') !== null;

    // Always run the search — with empty filters this returns everything.
    $results = $this->candidateSearch->search($filters);

    if ($hasSearched) {
        try {
            $this->candidateSearch->logSearch((int) session()->get('user_id'), $filters);
        } catch (\Throwable $e) {
            log_message('error', 'ResDex logSearch failed: ' . $e->getMessage());
        }
    }

    // ---- Per-candidate "saved search" state ----------------------------------
    // Each candidate card has its OWN save toggle (search filters + that
    // candidate's id). We look up the saved row per candidate by hash and
    // only mark it saved if a row exists AND is_manual = 1.
    if ($hasSearched && !empty($results['results'])) {
        $recruiterId = (int) session()->get('user_id');

        foreach ($results['results'] as &$candidate) {
            $candFilters = $filters;
            $candFilters['candidate_id'] = (string) $candidate['user_id'];

            $hash = $this->candidateSearch->hashFilters($candFilters);
            $row  = $this->candidateSearch->findSavedSearchByHash($recruiterId, $hash);

            $isManualSaved = !empty($row) && (int) ($row['is_manual'] ?? 0) === 1;

            $candidate['is_search_saved'] = $isManualSaved ? '1' : '0';
        }
        unset($candidate);
    }

    return view('recruiter/resdex', [
        'title'          => 'Search Resumes',
        'filters'        => $filters,
        'results'        => $results,
        'hasSearched'    => $hasSearched,
        'folders'        => $this->getFoldersForRecruiter(),
        'recentSearches' => $this->candidateSearch->getSearches((int) session()->get('user_id'), false, 4),
    ]);
}

    /** GET /recruiter/resdex/candidate/{id} */
    public function viewCandidate($id)
    {
        $profile = $this->candidateSearch->getFullProfile((int) $id);

        if (!$profile) {
            return redirect()->to(site_url('recruiter/resdex'))->with('error', 'Candidate not found.');
        }

        $db = \Config\Database::connect();
        $db->table('recruiter_candidate_actions')->insert([
            'candidate_id' => $id,
            'recruiter_id' => session()->get('user_id'),
            'action_type'  => 'viewed',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return view('recruiter/resdex_candidate', [
            'title'   => 'Candidate Profile',
            'profile' => $profile,
            'folders' => $this->getFoldersForRecruiter(),
        ]);
    }

    /** POST /recruiter/resdex/folder/add */
    public function addToFolder()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $folderId    = (int) $this->request->getPost('folder_id');
        $newFolder   = trim((string) $this->request->getPost('new_folder_name'));

        $db          = \Config\Database::connect();
        $recruiterId = session()->get('user_id');

        if ($newFolder !== '') {
            $db->table('resdex_folders')->insert([
                'recruiter_id' => $recruiterId,
                'folder_name'  => $newFolder,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $folderId = $db->insertID();
        }

        if ($folderId > 0 && $candidateId > 0) {
            $db->table('resdex_folder_candidates')->ignore(true)->insert([
                'folder_id'    => $folderId,
                'candidate_id' => $candidateId,
                'added_at'     => date('Y-m-d H:i:s'),
            ]);

            $db->table('recruiter_candidate_actions')->insert([
                'candidate_id' => $candidateId,
                'recruiter_id' => $recruiterId,
                'action_type'  => 'shortlisted',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->back()->with('success', 'Candidate added to folder.');
    }

    public function bulkAddToFolder()
    {
        $db          = \Config\Database::connect();
        $recruiterId = session()->get('user_id'); // adjust to your auth method

        if (!$recruiterId) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'You must be logged in to do this.',
            ]);
        }

        $folderId     = (int) $this->request->getPost('folder_id');
        $candidateIds = $this->request->getPost('candidate_ids') ?? [];

        if (!$folderId || empty($candidateIds)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Missing folder or candidates.',
            ]);
        }

        // Make sure this folder actually belongs to the logged-in recruiter,
        // so one recruiter can't bulk-save into another recruiter's folder.
        $folderOwned = $db->table('resdex_folders')
            ->where('id', $folderId)
            ->where('recruiter_id', $recruiterId)
            ->countAllResults();

        if (!$folderOwned) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Invalid folder.',
            ]);
        }

        $savedCount = 0;

        foreach ($candidateIds as $candidateId) {
            $candidateId = (int) $candidateId;

            if ($folderId > 0 && $candidateId > 0) {
                $db->table('resdex_folder_candidates')
                    ->ignore(true)
                    ->insert([
                        'folder_id'    => $folderId,
                        'candidate_id' => $candidateId,
                        'added_at'     => date('Y-m-d H:i:s'),
                    ]);

                $db->table('recruiter_candidate_actions')
                    ->ignore(true)
                    ->insert([
                        'candidate_id' => $candidateId,
                        'recruiter_id' => $recruiterId,
                        'action_type'  => 'shortlisted',
                        'created_at'   => date('Y-m-d H:i:s'),
                    ]);

                $savedCount++;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => $savedCount . ' candidate(s) saved to folder.',
        ]);
    }

    /** POST /recruiter/resdex/folder/create — standalone "+ New Folder" button on the Folders page */
    public function createFolder()
    {
        $name = trim((string) $this->request->getPost('folder_name'));

        if ($name === '') {
            return redirect()->back()->with('error', 'Please give the folder a name.');
        }

        $db = \Config\Database::connect();
        $db->table('resdex_folders')->insert([
            'recruiter_id' => session()->get('user_id'),
            'folder_name'  => $name,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Folder created.');
    }

    public function deleteFolders()
    {
        $recruiterId = (int) session()->get('user_id');
        $db          = \Config\Database::connect();

        // Support both a single folder_id (kept for potential future single-delete UI)
        // and bulk folder_ids[] from the Gmail-style multi-select.
        $folderIds = $this->request->getPost('folder_ids');

        if (empty($folderIds)) {
            $singleId  = (int) $this->request->getPost('folder_id');
            $folderIds = $singleId > 0 ? [$singleId] : [];
        } else {
            $folderIds = array_values(array_unique(array_filter(array_map('intval', (array) $folderIds))));
        }

        if (empty($folderIds)) {
            $message = 'No folders selected.';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(422);
            }

            return redirect()->back()->with('error', $message);
        }

        $db->transStart();

        // Only delete folders that actually belong to this recruiter — never trust
        // folder_ids from the client alone, since another recruiter's IDs could be
        // guessed/submitted otherwise.
        $ownedFolders = $db->table('resdex_folders')
            ->select('id')
            ->where('recruiter_id', $recruiterId)
            ->whereIn('id', $folderIds)
            ->get()
            ->getResultArray();

        $ownedIds = array_map(fn($row) => (int) $row['id'], $ownedFolders);

        if (!empty($ownedIds)) {
            // Remove the folder's candidate links first, if that's not already
            // handled by an ON DELETE CASCADE foreign key in your schema.
            $db->table('resdex_folder_candidates')->whereIn('folder_id', $ownedIds)->delete();
            $db->table('resdex_folders')->whereIn('id', $ownedIds)->delete();
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            $message = 'Could not delete folders. Please try again.';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(500);
            }

            return redirect()->back()->with('error', $message);
        }

        $count   = count($ownedIds);
        $message = $count . ' folder' . ($count === 1 ? '' : 's') . ' deleted.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'     => true,
                'deleted_ids' => $ownedIds,
                'message'     => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /** POST /recruiter/resdex/folder/remove */
    public function removeFromFolder()
    {
        $folderId    = (int) $this->request->getPost('folder_id');
        $recruiterId = (int) session()->get('user_id');

        // Support both the old single candidate_id (kept for backward compatibility)
        // and the new bulk candidate_ids[] array from the Gmail-style multi-select.
        $candidateIds = $this->request->getPost('candidate_ids');

        if (empty($candidateIds)) {
            $singleId     = (int) $this->request->getPost('candidate_id');
            $candidateIds = $singleId > 0 ? [$singleId] : [];
        } else {
            $candidateIds = array_values(array_unique(array_filter(array_map('intval', (array) $candidateIds))));
        }

        if ($folderId <= 0 || empty($candidateIds)) {
            $message = 'No candidates selected.';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(422);
            }

            return redirect()->back()->with('error', $message);
        }

        $removedCount = 0;
        foreach ($candidateIds as $candidateId) {
            $this->candidateSearch->removeFromFolder($folderId, $candidateId, $recruiterId);
            $removedCount++;
        }

        $message = $removedCount . ' candidate' . ($removedCount === 1 ? '' : 's') . ' removed from folder.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'     => true,
                'removed_ids' => $candidateIds,
                'message'     => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * POST /recruiter/resdex/save-search
     *
     * Toggles save/unsave for ONE CANDIDATE CARD within the current search —
     * NOT the whole search. Each card on the results page posts its own hidden
     * form containing the search filters PLUS that card's candidate_id, so the
     * hash (and therefore the saved row) is unique per candidate. This is why
     * clicking one card's bookmark no longer lights up every other card.
     */
    public function saveSearch()
    {
        try {
            $filters     = $this->collectFilters('post');
            $candidateId = (int) $this->request->getPost('candidate_id');

            if ($candidateId <= 0) {
                throw new \RuntimeException('Missing candidate reference for this save action.');
            }

            // Fold the candidate into the filter set BEFORE hashing, so every
            // card's saved-search row is unique to (filters + this candidate).
            $filters['candidate_id'] = (string) $candidateId;

            $recruiterId = (int) session()->get('user_id');
            $hash        = $this->candidateSearch->hashFilters($filters);

            $existing = $this->candidateSearch->findSavedSearchByHash($recruiterId, $hash);
            $db       = \Config\Database::connect();

            if ($existing) {
                // Already saved -> toggle OFF (only for this candidate's row)
                $db->table('resdex_saved_searches')->where('id', $existing['id'])->delete();
                $isSaved = false;
                $message = 'Removed from Saved Searches.';
            } else {
                // Not saved yet -> toggle ON, auto-named from the filters
                $candidateName = trim((string) $this->request->getPost('candidate_name'));
                $label         = $this->candidateSearch->buildSearchLabel($filters);
                if ($candidateName !== '') {
                    $label = $label !== 'Untitled search'
                        ? $candidateName . ' — ' . $label
                        : $candidateName;
                }

                $db->table('resdex_saved_searches')->insert([
                    'recruiter_id'    => $recruiterId,
                    'search_name'     => $label,
                    'is_manual'       => 1,
                    'filters_json'    => json_encode($filters),
                    'filters_hash'    => $hash,
                    'alert_frequency' => 'none',
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
                $isSaved = true;
                $message = 'Search saved.';
            }

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success'      => true,
                    'is_saved'     => $isSaved,
                    'candidate_id' => $candidateId,
                    'message'      => $message,
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Throwable $e) {
            log_message('error', 'saveSearch failed: ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Could not update saved search.',
                ])->setStatusCode(500);
            }

            return redirect()->back()->with('error', 'Could not update saved search.');
        }
    }

    /**
     * POST /recruiter/resdex/saved-searches/delete
     * Deletes one OR many saved/recent searches, scoped to the owning recruiter.
     * Accepts either a single 'id' (existing per-row delete button/form) or a
     * bulk 'ids[]' array (new checkbox multi-select on Manage Searches) —
     * same route + function name either way, mirroring deleteFolders().
     */
    public function deleteSearch()
    {
        $recruiterId = (int) session()->get('user_id');

        $ids = $this->request->getPost('ids');

        if (empty($ids)) {
            $singleId = (int) $this->request->getPost('id');
            $ids      = $singleId > 0 ? [$singleId] : [];
        } else {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
        }

        if (empty($ids)) {
            $message = 'No searches selected.';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(422);
            }

            return redirect()->back()->with('error', $message);
        }

        $deletedIds = $this->candidateSearch->deleteSearches($ids, $recruiterId);
        $count      = count($deletedIds);
        $message    = $count . ' search' . ($count === 1 ? '' : 'es') . ' removed.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'     => true,
                'deleted_ids' => $deletedIds,
                'message'     => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }


    /** GET /recruiter/resdex/saved-searches — "Manage Searches" (tabs: saved / recent) */
    public function savedSearches()
    {
        $recruiterId = (int) session()->get('user_id');
        $tab         = $this->request->getGet('tab') === 'recent' ? 'recent' : 'saved';

        $searches = $this->candidateSearch->getSearches($recruiterId, $tab === 'saved');

        return view('recruiter/resdex_saved_searches', [
            'title'     => 'Manage Searches',
            'searches'  => $searches,
            'activeTab' => $tab,
        ]);
    }

    /** GET /recruiter/resdex/folders */
    public function folders()
    {
        $db          = \Config\Database::connect();
        $recruiterId = session()->get('user_id');

        $folders = $db->table('resdex_folders')
            ->where('recruiter_id', $recruiterId)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        foreach ($folders as &$folder) {
            $folder['candidate_count'] = $db->table('resdex_folder_candidates')
                ->where('folder_id', $folder['id'])->countAllResults();
        }

        return view('recruiter/resdex_folders', [
            'title'   => 'My Folders',
            'folders' => $folders,
        ]);
    }

    /** GET /recruiter/resdex/folders/{id} */
    public function folderDetail($id)
    {
        $recruiterId = (int) session()->get('user_id');
        $data        = $this->candidateSearch->getFolderCandidates((int) $id, $recruiterId);

        if ($data === null) {
            return redirect()->to(site_url('recruiter/resdex/folders'))->with('error', 'Folder not found.');
        }

        return view('recruiter/resdex_folder_detail', [
            'title'      => $data['folder']['folder_name'],
            'folder'     => $data['folder'],
            'candidates' => $data['candidates'],
        ]);
    }

    private function getFoldersForRecruiter(): array
    {
        $db = \Config\Database::connect();
        return $db->table('resdex_folders')
            ->where('recruiter_id', session()->get('user_id'))
            ->orderBy('folder_name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Builds the standard filters array from either GET (main search form)
     * or POST (hidden save-search form). Both branches normalize types
     * identically so hashFilters() produces the SAME hash for the same
     * logical search regardless of which source built the array.
     *
     * Note: 'candidate_id' is intentionally NOT part of this fixed list —
     * it's merged in separately by saveSearch()/index() so it only ever
     * affects the per-candidate save hash, not the base search filters.
     *
     * @param string $source 'get' or 'post'
     */
    private function collectFilters(string $source = 'get'): array
    {
        $src = $source === 'post'
            ? ($this->request->getPost() ?? [])
            : ($this->request->getGet() ?? []);

        return [
            'keywords'         => (string) ($src['keywords'] ?? ''),
            'boolean_on'       => !empty($src['boolean_on']) ? '1' : '0',
            'mandatory'        => !empty($src['mandatory']) ? '1' : '0',
            'keyword_exclude'  => (string) ($src['keyword_exclude'] ?? ''),
            'it_skills'        => (string) ($src['it_skills'] ?? ''),
            'location'         => (string) ($src['location'] ?? ''),
            'exp_min'          => (string) ($src['exp_min'] ?? ''),
            'exp_max'          => (string) ($src['exp_max'] ?? ''),
            'salary_min'       => (string) ($src['salary_min'] ?? ''),
            'salary_max'       => (string) ($src['salary_max'] ?? ''),
            'notice_period'    => (string) ($src['notice_period'] ?? ''),
            'employment_type'  => (string) ($src['employment_type'] ?? ''),
            'education'        => (string) ($src['education'] ?? ''),
            'gender'           => (string) ($src['gender'] ?? ''),
            'must_have_skills' => array_filter(explode(',', (string) ($src['must_have_skills'] ?? ''))),
            'page'             => (int) ($src['page'] ?? 1),
            'per_page'         => 20,
        ];
    }
}