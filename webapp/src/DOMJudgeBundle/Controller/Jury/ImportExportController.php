<?php declare(strict_types=1);

namespace DOMJudgeBundle\Controller\Jury;

use Collator;
use Doctrine\ORM\EntityManagerInterface;
use DOMJudgeBundle\Controller\BaseController;
use DOMJudgeBundle\Entity\Clarification;
use DOMJudgeBundle\Entity\Contest;
use DOMJudgeBundle\Entity\ContestProblem;
use DOMJudgeBundle\Entity\Team;
use DOMJudgeBundle\Entity\TeamCategory;
use DOMJudgeBundle\Form\Type\BaylorCmsType;
use DOMJudgeBundle\Form\Type\ContestExportType;
use DOMJudgeBundle\Form\Type\ContestImportType;
use DOMJudgeBundle\Form\Type\TsvImportType;
use DOMJudgeBundle\Service\BaylorCmsService;
use DOMJudgeBundle\Service\DOMJudgeService;
use DOMJudgeBundle\Service\EventLogService;
use DOMJudgeBundle\Service\ImportExportService;
use DOMJudgeBundle\Service\ScoreboardService;
use DOMJudgeBundle\Utils\Scoreboard\Filter;
use DOMJudgeBundle\Utils\Scoreboard\ScoreboardMatrixItem;
use DOMJudgeBundle\Utils\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route("/jury/import-export")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ImportExportController extends BaseController
{
    /**
     * @var BaylorCmsService
     */
    protected $baylorCmsService;

    /**
     * @var ImportExportService
     */
    protected $importExportService;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ScoreboardService
     */
    protected $scoreboardService;

    /**
     * @var DOMJudgeService
     */
    protected $DOMJudgeService;

    /**
     * @var EventLogService
     */
    protected $eventLogService;

    /** @var string */
    protected $domjudgeVersion;

    /**
     * ImportExportController constructor.
     * @param BaylorCmsService       $baylorCmsService
     * @param ImportExportService    $importExportService
     * @param EntityManagerInterface $entityManager
     * @param ScoreboardService      $scoreboardService
     * @param DOMJudgeService        $DOMJudgeService
     * @param EventLogService        $eventLogService
     * @param string                 $domjudgeVersion
     */
    public function __construct(
        BaylorCmsService $baylorCmsService,
        ImportExportService $importExportService,
        EntityManagerInterface $entityManager,
        ScoreboardService $scoreboardService,
        DOMJudgeService $DOMJudgeService,
        EventLogService $eventLogService,
        string $domjudgeVersion
    ) {
        $this->baylorCmsService    = $baylorCmsService;
        $this->importExportService = $importExportService;
        $this->entityManager       = $entityManager;
        $this->scoreboardService   = $scoreboardService;
        $this->DOMJudgeService     = $DOMJudgeService;
        $this->eventLogService     = $eventLogService;
        $this->domjudgeVersion     = $domjudgeVersion;
    }

    /**
     * @Route("", name="jury_import_export")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function indexAction(Request $request)
    {
        $tsvForm = $this->createForm(TsvImportType::class);

        $tsvForm->handleRequest($request);

        if ($tsvForm->isSubmitted() && $tsvForm->isValid()) {
            $type  = $tsvForm->get('type')->getData();
            $file  = $tsvForm->get('file')->getData();
            $count = $this->importExportService->importTsv($type, $file);
            $this->addFlash('tsvImport', sprintf('%d items imported', $count));
            return $this->redirectToRoute('jury_import_export');
        }

        $baylorForm = $this->createForm(BaylorCmsType::class);

        $baylorForm->handleRequest($request);

        if ($baylorForm->isSubmitted() && $baylorForm->isValid()) {
            $contestId   = $baylorForm->get('contest_id')->getData();
            $accessToken = $baylorForm->get('access_token')->getData();
            if ($baylorForm->get('fetch_teams')->isClicked()) {
                $this->baylorCmsService->importTeams($accessToken, $contestId);
                $this->addFlash('baylorCms', 'Teams successfully imported');
            } else {
                $this->baylorCmsService->uploadStandings($accessToken, $contestId);
                $this->addFlash('baylorCms', 'Standings successfully uploaded');
            }
            return $this->redirectToRoute('jury_import_export');
        }

        return $this->render('@DOMJudge/jury/import_export.html.twig', [
            'tsv_form' => $tsvForm->createView(),
            'baylor_form' => $baylorForm->createView(),
        ]);
    }

    /**
     * @Route("/contest-yaml", name="jury_import_export_yaml")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function contestYamlAction(Request $request)
    {
        $exportForm = $this->createForm(ContestExportType::class);

        $exportForm->handleRequest($request);

        if ($exportForm->isSubmitted() && $exportForm->isValid()) {
            /** @var Contest $contest */
            $contest  = $exportForm->get('contest')->getData();
            $response = new StreamedResponse();
            $response->setCallback(function () use ($contest) {
                echo Yaml::dump($this->importExportService->getContestYamlData($contest));
            });
            $response->headers->set('Content-Type', 'application/x-yaml');
            $response->headers->set('Content-Disposition', 'attachment; filename="contest.yaml"');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Connection', 'Keep-Alive');
            $response->headers->set('Accept-Ranges', 'bytes');

            return $response;
        }

        $importForm = $this->createForm(ContestImportType::class);

        $importForm->handleRequest($request);

        if ($importForm->isSubmitted() && $importForm->isValid()) {
            /** @var UploadedFile $file */
            $file = $importForm->get('file')->getData();
            $data = Yaml::parseFile($file->getRealPath(), Yaml::PARSE_DATETIME);
            $this->importExportService->importContestYaml($data);
            $this->addFlash('yamlImport',
                            sprintf('The file %s is successfully imported.', $file->getClientOriginalName()));
            return $this->redirectToRoute('jury_import_export_yaml');
        }

        return $this->render('@DOMJudge/jury/import_export_contest_yaml.html.twig', [
            'export_form' => $exportForm->createView(),
            'import_form' => $importForm->createView(),
        ]);
    }

    /**
     * @Route("/export/{type}.tsv", name="jury_tsv_export", requirements={"type": "(groups|teams|scoreboard|results)"})
     * @param string $type
     * @return StreamedResponse
     * @throws \Exception
     */
    public function exportTsvAction(string $type)
    {
        $data    = [];
        $version = 1;
        switch ($type) {
            case 'groups':
                $data = $this->importExportService->getGroupData();
                break;
            case 'teams':
                $data = $this->importExportService->getTeamData();
                break;
            case 'scoreboard':
                $data = $this->importExportService->getScoreboardData();
                break;
            case 'results':
                $data = $this->importExportService->getResultsData();
                break;
        }

        $response = new StreamedResponse();
        $response->setCallback(function () use ($type, $version, $data) {
            echo sprintf("%s\t%s\n", $type, $version);
            // output the rows, filtering out any tab characters in the data
            foreach ($data as $row) {
                echo implode("\t", str_replace("\t", " ", $row)) . "\n";
            }
        });
        $filename = sprintf('%s.tsv', $type);
        $response->headers->set('Content-Type', sprintf('text/plain; name="%s"; charset=utf-8', $filename));
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    /**
     * @Route("/export/{type}.html", name="jury_html_export", requirements={"type":
     *                               "(results|results-icpc|clarifications)"})
     * @param Request $request
     * @param string  $type
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function exportHtmlAction(Request $request, string $type)
    {
        switch ($type) {
            case 'results':
            case 'results-icpc':
                return $this->getResultsHtml($request, $type === 'results-icpc');
            case 'clarifications':
                return $this->getClarificationsHtml();
        }
    }

    /**
     * Get the results HTML
     * @param Request $request
     * @param bool    $useIcpcLayout
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    protected function getResultsHtml(Request $request, bool $useIcpcLayout)
    {
        /** @var TeamCategory[] $categories */
        $categories  = $this->entityManager->createQueryBuilder()
            ->from('DOMJudgeBundle:TeamCategory', 'c', 'c.categoryid')
            ->select('c')
            ->where('c.visible = 1')
            ->getQuery()
            ->getResult();
        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[] = $category->getCategoryid();
        }

        $contest = $this->DOMJudgeService->getCurrentContest();
        if ($contest === null) {
            throw new BadRequestHttpException('No current contest');
        }

        $scoreIsInSeconds = (bool)$this->DOMJudgeService->dbconfig_get('score_in_seconds', false);
        $filter           = new Filter();
        $filter->setCategories($categoryIds);
        $scoreboard = $this->scoreboardService->getScoreboard($contest, true, $filter);
        $teams      = $scoreboard->getTeams();

        $useExternalId = $this->eventLogService->externalIdFieldForEntity(Team::class) !== null;

        $teamNames = [];
        foreach ($teams as $team) {
            $teamNames[$useExternalId ? $team->getExternalid() : $team->getTeamid()] = $team->getName();
        }

        $awarded       = [];
        $ranked        = [];
        $honorable     = [];
        $regionWinners = [];

        // TODO: allow to specify this
        $sortOrder = 0;

        foreach ($this->importExportService->getResultsData($sortOrder) as $row) {
            $team = $teamNames[$row[0]];

            if ($row[6] !== '') {
                $regionWinners[] = [
                    'group' => $row[6],
                    'team' => $team,
                ];
            }

            $row = [
                'team' => $team,
                'rank' => $row[1],
                'award' => $row[2],
                'solved' => $row[3],
                'total_time' => $row[4],
                'max_time' => $row[5],
            ];
            if (preg_match('/^(.*) Medal$/', $row['award'], $matches)) {
                $row['class'] = strtolower($matches[1]);
            } else {
                $row['class'] = '';
            }
            if ($row['rank'] === '') {
                $honorable[] = $row['team'];
            } elseif ($row['award'] === 'Ranked') {
                $ranked[] = $row;
            } else {
                $awarded[] = $row;
            }
        }

        usort($regionWinners, function ($a, $b) {
            return $a['group'] <=> $b['group'];
        });

        $collator = new Collator('en_US');
        $collator->sort($honorable);

        $problems     = $scoreboard->getProblems();
        $matrix       = $scoreboard->getMatrix();
        $firstToSolve = [];

        foreach ($problems as $problem) {
            $firstToSolve[$problem->getProbid()] = [
                'problem' => $problem->getShortname(),
                'problem_name' => $problem->getProblem()->getName(),
                'team' => null,
                'time' => null,
            ];
            foreach ($teams as $team) {
                if (!isset($categories[$team->getCategoryid()]) || $team->getCategory()->getSortorder() !== $sortOrder) {
                    continue;
                }

                /** @var ScoreboardMatrixItem $matrixItem */
                $matrixItem = $matrix[$team->getTeamid()][$problem->getProbid()];
                if ($matrixItem->isCorrect() && $scoreboard->solvedFirst($team, $problem)) {
                    $firstToSolve[$problem->getProbid()] = [
                        'problem' => $problem->getShortname(),
                        'problem_name' => $problem->getProblem()->getName(),
                        'team' => $teamNames[$useExternalId ? $team->getExternalid() : $team->getTeamid()],
                        'time' => Utils::scoretime($matrixItem->getTime(), $scoreIsInSeconds),
                    ];
                }
            }
        }

        usort($firstToSolve, function ($a, $b) {
            if ($a['time'] === null) {
                $a['time'] = PHP_INT_MAX;
            }
            if ($b['time'] === null) {
                $b['time'] = PHP_INT_MAX;
            }
            if ($a['time'] === $b['time']) {
                return $a['problem'] <=> $b['problem'];
            }
            return $a['time'] <=> $b['time'];
        });

        $data = [
            'awarded' => $awarded,
            'ranked' => $ranked,
            'honorable' => $honorable,
            'regionWinners' => $regionWinners,
            'firstToSolve' => $firstToSolve,
            'domjudgeVersion' => $this->domjudgeVersion,
            'title' => sprintf('Results for %s', $contest->getName()),
            'download' => $request->query->getBoolean('download'),
        ];
        if ($useIcpcLayout) {
            $response = $this->render('@DOMJudge/jury/export/results_icpc.html.twig', $data);
        } else {
            $response = $this->render('@DOMJudge/jury/export/results.html.twig', $data);
        }

        if ($request->query->getBoolean('download')) {
            $response->headers->set('Content-disposition', 'attachment; filename=results.html');
        }

        return $response;
    }

    /**
     * Get the clarifications HTML
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    protected function getClarificationsHtml()
    {
        $contest = $this->DOMJudgeService->getCurrentContest();
        if ($contest === null) {
            throw new BadRequestHttpException('No current contest');
        }

        $queues              = (array)$this->DOMJudgeService->dbconfig_get('clar_queues');
        $clarificationQueues = [null => 'Unassigned issues'];
        foreach ($queues as $key => $val) {
            $clarificationQueues[$key] = $val;
        }

        $categories = (array)$this->DOMJudgeService->dbconfig_get('clar_categories');

        $clarificationCategories = [];
        foreach ($categories as $key => $val) {
            $clarificationCategories[$key] = $val;
        }

        /** @var Clarification[] $clarifications */
        $clarifications = $this->entityManager->createQueryBuilder()
            ->from('DOMJudgeBundle:Clarification', 'c')
            ->select('c')
            ->andWhere('c.contest = :contest')
            ->setParameter(':contest', $contest)
            ->addOrderBy('c.category')
            ->addOrderBy('c.probid')
            ->addOrderBy('c.submittime')
            ->addOrderBy('c.clarid')
            ->getQuery()
            ->getResult();

        $grouped = [];

        foreach ($clarifications as $clarification) {
            $queue = $clarification->getQueue();

            if (!$clarification->getInReplyTo()) {
                if (!isset($grouped[$queue])) {
                    $grouped[$queue] = [];
                }
                $grouped[$queue][$clarification->getClarid()] = $clarification;
            }
        }

        /** @var ContestProblem[] $contestProblems */
        $contestProblems = $this->entityManager->createQueryBuilder()
            ->from('DOMJudgeBundle:ContestProblem', 'cp', 'cp.probid')
            ->select('cp')
            ->andWhere('cp.contest = :contest')
            ->setParameter(':contest', $contest)
            ->getQuery()
            ->getResult();

        return $this->render('@DOMJudge/jury/export/clarifications.html.twig', [
            'domjudgeVersion' => $this->domjudgeVersion,
            'title' => sprintf('Clarifications for %s', $contest->getName()),
            'grouped' => $grouped,
            'queues' => $clarificationQueues,
            'categories' => $clarificationCategories,
            'contest' => $contest,
            'problems' => $contestProblems,
        ]);
    }
}
