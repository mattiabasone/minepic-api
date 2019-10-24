<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core as MinepicCore;
use App\Misc\SplashMessage;
use Illuminate\Http\Response;
use App\Database\AccountsStats;
use App\Helpers\Date as DateHelper;
use Laravel\Lumen\Http\ResponseFactory;
use Laravel\Lumen\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class WebsiteController
 */
class WebsiteController extends BaseController
{
    /**
     * Default title.
     *
     * @var string
     */
    private const DEFAULT_PAGE_TITLE = 'Minecraft avatar generator - Minepic';

    /**
     * Default description.
     *
     * @var string
     */
    private const DEFAULT_PAGE_DESCRIPTION = 'MinePic is a free Minecraft avatar and skin viewer '.
    'that allow users and developers to pick them for their projects';

    /**
     * Default keywords.
     *
     * @var string
     */
    private const DEFAULT_PAGE_KEYWORDS = 'Minecraft, Minecraft avatar viewer, pic, minepic avatar viewer, skin, '.
    'minecraft skin, avatar, minecraft avatar, generator, skin generator, skin viewer';

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * WebsiteController constructor.
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        ResponseFactory $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    private function composeView(
        string $page = '',
        array $bodyData = [],
        array $headerData = []
    ): string {
        return view('public.template.header', $headerData).
                view('public.'.$page, $bodyData).
                view('public.template.footer');
    }

    /**
     * Render fullpage (headers, body, footer).
     *
     * @param string $page
     * @param array  $bodyData
     * @param array  $headerData
     *
     * @return Response
     */
    private function renderPage(
        string $page = '',
        array $bodyData = [],
        array $headerData = []
    ): Response {
        $realHeaderData = [];
        $realHeaderData['title'] = $headerData['title'] ?? self::DEFAULT_PAGE_TITLE;
        $realHeaderData['description'] = $headerData['description'] ?? self::DEFAULT_PAGE_DESCRIPTION;
        $realHeaderData['keywords'] = $headerData['keywords'] ?? self::DEFAULT_PAGE_KEYWORDS;
        $realHeaderData['randomMessage'] = SplashMessage::get();

        $view = $this->composeView($page, $bodyData, $realHeaderData);
        return $this->responseFactory->make(
            $view,
            Response::HTTP_OK
        );
    }

    /**
     * Index.
     *
     * @return Response
     */
    public function index(): Response
    {
        $bodyData = [
            'lastRequests' => AccountsStats::getLastUsers(),
            'mostWanted' => AccountsStats::getMostWanted(),
        ];

        return $this->renderPage('index', $bodyData);
    }

    /**
     * User stats page.
     *
     * @param string $uuidOrName
     *
     * @return Response
     */
    public function user(string $uuidOrName): Response
    {
        $minepicCore = new MinepicCore();

        if ($minepicCore->initialize($uuidOrName)) {
            [$userdata, $userstats] = $minepicCore->getFullUserdata();

            $headerData = [
                'title' => $userdata->username.' usage statistics - Minepic',
                'description' => 'MinePic usage statistics for the user '.$userdata->username,
                'keywords' => 'Minecraft, Minecraft avatar viewer, pic, minepic avatar viewer, skin, '.
                    'minecraft skin, avatar, minecraft avatar, generator, skin generator, skin viewer',
            ];

            $bodyData = [
                'user' => [
                    'uuid' => $userdata->uuid,
                    'username' => $userdata->username,
                    'count_request' => $userstats->count_request,
                    'count_search' => $userstats->count_search,
                    'last_request' => DateHelper::humanizeTimestamp($userstats->time_request),
                    'last_search' => DateHelper::humanizeTimestamp($userstats->time_search),
                ],
            ];

            return $this->renderPage('user', $bodyData, $headerData);
        }

        throw new NotFoundHttpException();
    }
}
