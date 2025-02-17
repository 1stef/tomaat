<?php
namespace App\Security;

use App\Entity\User;
use App\Repository\ToernooiRepository;
use App\Repository\ToernooiAdminRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class ToernooiVoter extends Voter
{
    // these strings are just invented: you can use anything
    const string AANVRAGER = 'ROLE_AANVRAGER';
    const string TOERNOOIADMIN = 'ROLE_ADMIN_TOERNOOI';
    const string VOORBEREIDEN = 'VOORBEREIDEN';
    const string INSCHRIJVEN = 'INSCHRIJVEN';
    const string PLANNEN = 'PLANNEN';
    const string SPELEN = 'SPELEN';
    const string INSCHRIJVEN_SPELEN = 'INSCHRIJVEN_SPELEN';

    private RequestStack $requestStack;
    private ToernooiRepository $toernooiRepository;
    private ToernooiAdminRepository $toernooiAdminRepository;

    public function __construct(
        RequestStack $requestStack,
        ToernooiRepository $toernooiRepository,
        ToernooiAdminRepository $toernooiAdminRepository)
    {
        $this->requestStack = $requestStack;
        $this->toernooiRepository = $toernooiRepository;
        $this->toernooiAdminRepository = $toernooiAdminRepository;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::AANVRAGER, self::TOERNOOIADMIN, self::VOORBEREIDEN,
                            self::INSCHRIJVEN, self::PLANNEN, self::SPELEN, self::INSCHRIJVEN_SPELEN] )) {
            return false;
        }

        // only vote on `Toernooi` objects
        // if (!$subject instanceof Toernooi) {
        // Negeer $subject hier:

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        //error_log("ToernooiVoter, voteOnAttribute() ".$attribute.", ".print_r($subject, true));
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // Haal het toernooi_id uit de session, indien gezet:
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!is_numeric($toernooi_id)){
            return false;
        }
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        $toernooiStatus = $toernooi->getToernooiStatus();

        switch ($attribute) {
            case self::AANVRAGER:
                return $this->isAanvrager($toernooi, $user);
            case self::TOERNOOIADMIN:
                return $this->isToernooiAdmin($toernooi, $user);
            case self::VOORBEREIDEN:
                return $toernooiStatus == "voorbereiden inschrijving";
            case self::INSCHRIJVEN:
                return $toernooiStatus == "inschrijven";
            case self::PLANNEN:
                return $toernooiStatus == "plannen";
            case self::SPELEN:
                return $toernooiStatus == "spelen";
            case self::INSCHRIJVEN_SPELEN:
                return ($toernooiStatus == "spelen") || ($toernooiStatus == "inschrijven");
            }

        throw new \LogicException('This code should not be reached!');
    }

    private function isAanvrager($toernooi, User $user): bool
    {
        return ($toernooi->getAdminId() == $user->getId() && $toernooi->getToernooiStatus() != 'afgesloten');
    }

    private function isToernooiAdmin($toernooi, User $user): bool
    {
        if ($this->isAanvrager($toernooi, $user)){
            return true;
        }
        return($this->toernooiAdminRepository->IsUserToernooiAdmin($toernooi->getId(), $user->getEmail()));
    }
}
