<?php declare(strict_types=1);
namespace DOMJudgeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scoreboard cache
 * @ORM\Entity()
 * @ORM\Table(name="scorecache", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 */
class ScoreCache
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="submissions_restricted", options={"comment"="Number of submissions made (restricted audiences)"}, nullable=false)
     */
    private $submissions_restricted;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="pending_restricted", options={"comment"="Number of submissions pending judgement (restricted audiences)"}, nullable=false)
     */
    private $pending_restricted;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="is_correct_restricted", options={"comment"="Has there been a correct submission? (restricted audiences)"}, nullable=false)
     */
    private $is_correct_restricted = false;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="solvetime_restricted", options={"comment"="Seconds into contest when problem solved (restricted audiences)", "unsigned"=true}, nullable=false)
     */
    private $solvetime_restricted;


    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="submissions_public", options={"comment"="Number of submissions made (public)"}, nullable=false)
     */
    private $submissions_public;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="pending_public", options={"comment"="Number of submissions pending judgement (public)"}, nullable=false)
     */
    private $pending_public;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="is_correct_public", options={"comment"="Has there been a correct submission? (public)"}, nullable=false)
     */
    private $is_correct_public = false;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="solvetime_public", options={"comment"="Seconds into contest when problem solved (public)", "unsigned"=true}, nullable=false)
     */
    private $solvetime_public;



    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Contest", inversedBy="scorecache")
     * @ORM\JoinColumn(name="cid", referencedColumnName="cid")
     */
    private $contest;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="scorecache")
     * @ORM\JoinColumn(name="teamid", referencedColumnName="teamid")
     */
    private $team;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Problem", inversedBy="scorecache")
     * @ORM\JoinColumn(name="probid", referencedColumnName="probid")
     */
    private $problem;

    /**
     * Set submissionsRestricted
     *
     * @param integer $submissionsRestricted
     *
     * @return ScoreCache
     */
    public function setSubmissionsRestricted($submissionsRestricted)
    {
        $this->submissions_restricted = $submissionsRestricted;

        return $this;
    }

    /**
     * Get submissionsRestricted
     *
     * @return integer
     */
    public function getSubmissionsRestricted()
    {
        return $this->submissions_restricted;
    }

    /**
     * Set pendingRestricted
     *
     * @param integer $pendingRestricted
     *
     * @return ScoreCache
     */
    public function setPendingRestricted($pendingRestricted)
    {
        $this->pending_restricted = $pendingRestricted;

        return $this;
    }

    /**
     * Get pendingRestricted
     *
     * @return integer
     */
    public function getPendingRestricted()
    {
        return $this->pending_restricted;
    }

    /**
     * Set isCorrectRestricted
     *
     * @param boolean $isCorrectRestricted
     *
     * @return ScoreCache
     */
    public function setIsCorrectRestricted($isCorrectRestricted)
    {
        $this->is_correct_restricted = $isCorrectRestricted;

        return $this;
    }

    /**
     * Get isCorrectRestricted
     *
     * @return boolean
     */
    public function getIsCorrectRestricted()
    {
        return $this->is_correct_restricted;
    }

    /**
     * Set solvetimeRestricted
     *
     * @param double $solvetimeRestricted
     *
     * @return ScoreCache
     */
    public function setSolvetimeRestricted($solvetimeRestricted)
    {
        $this->solvetime_restricted = $solvetimeRestricted;

        return $this;
    }

    /**
     * Get solvetimeRestricted
     *
     * @return string
     */
    public function getSolvetimeRestricted()
    {
        return $this->solvetime_restricted;
    }

    /**
     * Set submissionsPublic
     *
     * @param integer $submissionsPublic
     *
     * @return ScoreCache
     */
    public function setSubmissionsPublic($submissionsPublic)
    {
        $this->submissions_public = $submissionsPublic;

        return $this;
    }

    /**
     * Get submissionsPublic
     *
     * @return integer
     */
    public function getSubmissionsPublic()
    {
        return $this->submissions_public;
    }

    /**
     * Set pendingPublic
     *
     * @param integer $pendingPublic
     *
     * @return ScoreCache
     */
    public function setPendingPublic($pendingPublic)
    {
        $this->pending_public = $pendingPublic;

        return $this;
    }

    /**
     * Get pendingPublic
     *
     * @return integer
     */
    public function getPendingPublic()
    {
        return $this->pending_public;
    }

    /**
     * Set isCorrectPublic
     *
     * @param boolean $isCorrectPublic
     *
     * @return ScoreCache
     */
    public function setIsCorrectPublic($isCorrectPublic)
    {
        $this->is_correct_public = $isCorrectPublic;

        return $this;
    }

    /**
     * Get isCorrectPublic
     *
     * @return boolean
     */
    public function getIsCorrectPublic()
    {
        return $this->is_correct_public;
    }

    /**
     * Set solvetimePublic
     *
     * @param double $solvetimePublic
     *
     * @return ScoreCache
     */
    public function setSolvetimePublic($solvetimePublic)
    {
        $this->solvetime_public = $solvetimePublic;

        return $this;
    }

    /**
     * Get solvetimePublic
     *
     * @return string
     */
    public function getSolvetimePublic()
    {
        return $this->solvetime_public;
    }

    /**
     * Set contest
     *
     * @param \DOMJudgeBundle\Entity\Contest $contest
     *
     * @return ScoreCache
     */
    public function setContest(\DOMJudgeBundle\Entity\Contest $contest = null)
    {
        $this->contest = $contest;

        return $this;
    }

    /**
     * Get contest
     *
     * @return \DOMJudgeBundle\Entity\Contest
     */
    public function getContest()
    {
        return $this->contest;
    }

    /**
     * Set team
     *
     * @param \DOMJudgeBundle\Entity\Team $team
     *
     * @return ScoreCache
     */
    public function setTeam(\DOMJudgeBundle\Entity\Team $team = null)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Get team
     *
     * @return \DOMJudgeBundle\Entity\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set problem
     *
     * @param \DOMJudgeBundle\Entity\Problem $problem
     *
     * @return ScoreCache
     */
    public function setProblem(\DOMJudgeBundle\Entity\Problem $problem = null)
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * Get problem
     *
     * @return \DOMJudgeBundle\Entity\Problem
     */
    public function getProblem()
    {
        return $this->problem;
    }

    /**
     * Get the number of public or restricted submissions based on the parameter
     * @param bool $restricted
     * @return int
     */
    public function getSubmissions(bool $restricted): int
    {
        return $restricted ? $this->getSubmissionsRestricted() : $this->getSubmissionsPublic();
    }

    /**
     * Get the number of public or restricted pending submissions based on the parameter
     * @param bool $restricted
     * @return int
     */
    public function getPending(bool $restricted): int
    {
        return $restricted ? $this->getPendingRestricted() : $this->getPendingPublic();
    }

    /**
     * Get the public or restricted solve time based on the parameter
     * @param bool $restricted
     * @return float|string
     */
    public function getSolveTime(bool $restricted)
    {
        return $restricted ? $this->getSolvetimeRestricted() : $this->getSolvetimePublic();
    }

    /**
     * Get whether the problem is publicly or restrictedly correct based on the parameter
     * @param bool $restricted
     * @return bool
     */
    public function getIsCorrect(bool $restricted): bool
    {
        return $restricted ? $this->getIsCorrectRestricted() : $this->getIsCorrectPublic();
    }
}
