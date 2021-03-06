<?php

namespace SpokaneFair;

class Entry {
	
	const TABLE_NAME = 'spokane_fair_entries';

	private $id;
	private $random_code;
	private $photographer_id;
	private $category_id;
	private $photo_post_id;
	private $width;
	private $height;
	private $title;
	private $is_finalist = FALSE;
	private $composition_score;
	private $impact_score;
	private $technical_score;
	private $total_score;
	private $created_at;
	private $updated_at;
	
	/** @var Photographer $photographer */
	private $photographer;
	
	/** @var Category $category */
	private $category;

	/**
	 * Entry constructor.
	 *
	 * @param null $id
	 */
	public function __construct( $id=NULL )
	{
		$this
			->setId( $id )
			->read();
	}

	public function create()
	{
		global $wpdb;

		if ( $this->photographer_id !== NULL && $this->category_id !== NULL && $this->photo_post_id !== NULL )
		{
			$this
				->setCreatedAt( time() )
				->setUpdatedAt( time() )
				->assignRandomCode();

			$wpdb->insert(
				$wpdb->prefix . self::TABLE_NAME,
				array(
					'random_code' => $this->random_code,
					'photographer_id' => $this->photographer_id,
					'category_id' => $this->category_id,
					'photo_post_id' => $this->photo_post_id,
					'width' => $this->width,
					'height' => $this->height,
					'title' => $this->title,
					'created_at' => $this->getCreatedAt( 'Y-m-d H:i:s' ),
					'updated_at' => $this->getUpdatedAt( 'Y-m-d H:i:s' )
				),
				array(
					'%d',
					'%d',
					'%d',
					'%d',
                    '%d',
                    '%d',
					'%s',
					'%s',
					'%s'
				)
			);

			$this->setId( $wpdb->insert_id );
		}
	}

	public function read()
	{
		global $wpdb;

		if ( $this->id !== NULL )
		{
			$sql = $wpdb->prepare( "
			SELECT
				e.*,
				c.code AS category_code,
				c.title AS category_title
			FROM
				" . $wpdb->prefix . self::TABLE_NAME . " e
				LEFT JOIN " . $wpdb->prefix . Category::TABLE_NAME . " c
					ON e.category_id = c.id
			WHERE
				e.id = %d",
				$this->id
			);

			if ( $row = $wpdb->get_row( $sql ) )
			{
				$this->loadFromRow( $row );
			}
			else
			{
				$this->setId( NULL );
			}
		}
	}

	/**
	 * @param \stdClass $row
	 */
	public function loadFromRow( \stdClass $row )
	{
		$this
			->setId( $row->id )
			->setRandomCode( $row->random_code )
			->setPhotographerId( $row->photographer_id )
			->setCategoryId( $row->category_id )
			->setPhotoPostId( $row->photo_post_id )
            ->setWidth($row->width)
            ->setHeight($row->height)
			->setTitle( $row->title )
			->setCreatedAt( $row->created_at )
			->setUpdatedAt( $row->updated_at );

		if ( $this->random_code === NULL )
		{
			$this
				->assignRandomCode()
				->update();
		}

		if ( property_exists( $row, 'is_finalist' ) )
        {
            $this
                ->setIsFinalist( $row->is_finalist )
                ->setCompositionScore( $row->composition_score )
                ->setImpactScore( $row->impact_score )
                ->setTechnicalScore( $row->technical_score );
        }

		if ( property_exists( $row, 'category_code' ) )
		{
			$category = new Category;
			$category
				->setId( $row->category_id )
				->setCode( $row->category_code )
				->setTitle( $row->category_title );

			$this->setCategory( $category );
		}

		if ( property_exists( $row, 'email' ) )
		{
			$photographer = new Photographer;
			$photographer
				->setId( $row->photographer_id )
				->setFirstName( $row->first_name )
				->setLastName( $row->last_name )
				->setEmail( $row->email )
				->setPhone( $row->phone )
				->setState( $row->state );

			$this->setPhotographer( $photographer );
		}
	}

	public function update()
	{
		global $wpdb;

		if ( $this->id !== NULL )
		{
			$this->setUpdatedAt( time() );

			$wpdb->update(
				$wpdb->prefix . self::TABLE_NAME,
				array(
					'random_code' => $this->random_code,
					'photographer_id' => $this->photographer_id,
					'category_id' => $this->category_id,
					'photo_post_id' => $this->photo_post_id,
					'height' => $this->height,
					'width' => $this->width,
					'title' => $this->title,
					'is_finalist' => $this->is_finalist,
					'composition_score' => $this->composition_score,
 					'impact_score' => $this->impact_score,
 					'technical_score' => $this->technical_score,
 					'total_score' => $this->getTotalScore(),
 					'updated_at' => $this->getUpdatedAt( 'Y-m-d H:i:s' )
				),
				array(
					'id' => $this->id
				),
				array(
					'%d',
					'%d',
					'%d',
                    '%d',
                    '%d',
					'%d',
					'%s',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
					'%s'
				),
				array(
					'%d'
				)
			);
		}
	}

	public function delete()
	{
		global $wpdb;

		if ( $this->id !== NULL )
		{
			$wpdb->delete(
				$wpdb->prefix . self::TABLE_NAME,
				array(
					'id' => $this->id
				),
				array(
					'%d'
				)
			);

			$this->setId( NULL );
		}
	}

	public function getCode( $add_extension = FALSE, $add_photographer_name = FALSE )
	{
		if ( $add_photographer_name )
		{
			return $this->getCategory()->getCode() . '-' . str_pad( $this->getRandomCode(), 4, '0', STR_PAD_LEFT ) . '_' . str_replace( '_', '-', $this->getTitle( TRUE ) ) . '_' . $this->photographer->getFullName( TRUE ) . '.jpg';
		}

		return $this->getCategory()->getCode() . '-' . str_pad( $this->getRandomCode(), 4, '0', STR_PAD_LEFT ) . '_' . $this->getTitle( TRUE ) . ( ( $add_extension ) ? '.jpg' : '' );
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param mixed $id
	 *
	 * @return Entry
	 */
	public function setId( $id )
	{
		$this->id = ( is_numeric( $id ) ) ? intval( $id ) : NULL;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRandomCode()
	{
		return $this->random_code;
	}

	/**
	 * @param mixed $random_code
	 *
	 * @return Entry
	 */
	public function setRandomCode( $random_code )
	{
		$this->random_code = ( is_numeric( $random_code ) ) ? intval( $random_code ) : NULL;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPhotographerId()
	{
		return $this->photographer_id;
	}

	/**
	 * @param mixed $photographer_id
	 *
	 * @return Entry
	 */
	public function setPhotographerId( $photographer_id )
	{
		$this->photographer_id = ( is_numeric( $photographer_id ) ) ? intval( $photographer_id ) : NULL;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCategoryId()
	{
		return $this->category_id;
	}

	/**
	 * @param mixed $category_id
	 *
	 * @return Entry
	 */
	public function setCategoryId( $category_id )
	{
		$this->category_id = ( is_numeric( $category_id ) ) ? intval( $category_id ) : NULL;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPhotoPostId()
	{
		return $this->photo_post_id;
	}

	/**
	 * @param mixed $photo_post_id
	 *
	 * @return Entry
	 */
	public function setPhotoPostId( $photo_post_id )
	{
		$this->photo_post_id = ( is_numeric( $photo_post_id ) ) ? intval( $photo_post_id ) : NULL;

		return $this;
	}

    /**
     * @return bool
     */
	public function hasDimensions()
    {
        return ($this->width !== null && $this->height !== null);
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return ($this->width === null) ? 0 : $this->width;
    }

    /**
     * @param mixed $width
     * @return Entry
     */
    public function setWidth($width)
    {
        $this->width = ( is_numeric( $width ) ) ? intval( $width ) : NULL;
        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return ($this->height === null) ? 0 : $this->height;
    }

    /**
     * @param mixed $height
     * @return Entry
     */
    public function setHeight($height)
    {
        $this->height = ( is_numeric( $height ) ) ? intval( $height ) : NULL;
        return $this;
    }

	/**
	 * @param bool $url_format
	 *
	 * @return string
	 */
	public function getTitle( $url_format=FALSE )
	{
		if ( $this->title === NULL )
		{
			return '';
		}
		else
		{
			$patterns = array();
			$replacements = array();

			$patterns[1] = '/[ ]/';
			$patterns[0] = '/[^a-zA-Z0-9_]/';
			$replacements[0] = '_';
			$replacements[1] = '';

			return ( $url_format ) ? preg_replace( $patterns, $replacements, $this->title ) : $this->title;
		}
	}

	/**
	 * @param mixed $title
	 *
	 * @return Entry
	 */
	public function setTitle( $title )
	{
		$this->title = $title;

		return $this;
	}

    /**
     * @return bool
     */
    public function isFinalist()
    {
        return ( $this->is_finalist === TRUE );
    }

    /**
     * @param $is_finalist
     *
     * @return $this
     */
    public function setIsFinalist( $is_finalist )
    {
        $this->is_finalist = ( $is_finalist === TRUE || $is_finalist == 1 );

        return $this;
    }

    /**
     * @return int
     */
    public function getCompositionScore()
    {
        return ( $this->composition_score === NULL ) ? 0 : $this->composition_score;
    }

    /**
     * @param $composition_score
     *
     * @return $this
     */
    public function setCompositionScore( $composition_score )
    {
        $this->composition_score = ( is_numeric( $composition_score ) ) ? intval( $composition_score ) : NULL;

        return $this;
    }

    /**
     * @return int
     */
    public function getImpactScore()
    {
        return ( $this->impact_score === NULL ) ? 0 : $this->impact_score;
    }

    /**
     * @param $impact_score
     *
     * @return $this
     */
    public function setImpactScore( $impact_score )
    {
        $this->impact_score = ( is_numeric( $impact_score ) ) ? intval( $impact_score ) : NULL;

        return $this;
    }

    /**
     * @return int
     */
    public function getTechnicalScore()
    {
        return ( $this->technical_score === NULL ) ? 0 : $this->technical_score;
    }

    /**
     * @param $technical_score
     *
     * @return $this
     */
    public function setTechnicalScore( $technical_score )
    {
        $this->technical_score = ( is_numeric( $technical_score ) ) ? intval( $technical_score ) : NULL;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalScore()
    {
        return $this->getCompositionScore() + $this->getImpactScore() + $this->getTechnicalScore();
    }

    /**
     * @param $total_score
     *
     * @return $this
     */
    public function setTotalScore( $total_score )
    {
        $this->total_score = $total_score;

        return $this;
    }

	/**
	 * @param null $format
	 *
	 * @return bool|string
	 */
	public function getCreatedAt( $format=NULL )
	{
		if ( $format === NULL )
		{
			return $this->created_at;
		}

		return ( $this->created_at === NULL ) ? '' : date( $format, $this->created_at );
	}

	/**
	 * @param mixed $created_at
	 *
	 * @return Entry
	 */
	public function setCreatedAt( $created_at )
	{
		$this->created_at = ( $created_at === NULL || is_numeric( $created_at ) ) ? $created_at : strtotime( $created_at );

		return $this;
	}

	/**
	 * @param null $format
	 *
	 * @return bool|string
	 */
	public function getUpdatedAt( $format=NULL )
	{
		if ( $format === NULL )
		{
			return $this->updated_at;
		}

		return ( $this->updated_at === NULL ) ? '' : date( $format, $this->updated_at );
	}

	/**
	 * @param mixed $updated_at
	 *
	 * @return Entry
	 */
	public function setUpdatedAt( $updated_at )
	{
		$this->updated_at = ( $updated_at === NULL || is_numeric( $updated_at ) ) ? $updated_at : strtotime( $updated_at );

		return $this;
	}

	/**
	 * @return Photographer
	 */
	public function getPhotographer()
	{
	    if ( $this->photographer === NULL )
        {
            $this->photographer = new Photographer( $this->photographer_id );
        }

		return $this->photographer;
	}

	/**
	 * @param Photographer $photographer
	 *
	 * @return Entry
	 */
	public function setPhotographer( $photographer )
	{
		$this->photographer = $photographer;

		return $this;
	}

	/**
	 * @return Category
	 */
	public function getCategory()
	{
		return $this->category;
	}

	/**
	 * @param Category $category
	 *
	 * @return Entry
	 */
	public function setCategory( $category )
	{
		$this->category = $category;

		return $this;
	}

	/**
	 * @param int $photographer_id
	 *
	 * @return Entry[]
	 */
	public static function getPhotographerEntries( $photographer_id )
	{
		global $wpdb;
		$photographer_id = ( is_numeric( $photographer_id ) ) ? intval( $photographer_id ) : 0;
		$entries = array();

		$sql = $wpdb->prepare("
			SELECT
				e.*,
				c.code AS category_code,
				c.title AS category_title
			FROM
				" . $wpdb->prefix . self::TABLE_NAME . " e
				LEFT JOIN " . $wpdb->prefix . Category::TABLE_NAME . " c
					ON e.category_id = c.id
			WHERE
				e.photographer_id = %d
			ORDER BY e.id DESC",
			$photographer_id
		);

		$rows = $wpdb->get_results( $sql );
		foreach( $rows as $row )
		{
			$entry = new Entry;
			$entry->loadFromRow( $row );
			$entries[ $entry->getId() ] = $entry;
		}

		return $entries;
	}

	/**
	 * @param string $sort
	 * @param string $dir
     * @param null|string $code
	 *
	 * @return Entry[]
	 */
	public static function getAllEntries( $sort='e.id', $dir='DESC', $code = NULL )
	{
		global $wpdb;
		$entries = array();

		$sorts = array( 'e.id', 'e.title', 'c.title', 'e.created_at', 'ln.last_name' );
		$sort = ( in_array( $sort, $sorts ) ) ? $sort : 'e.id';
		$dir = ( $dir == 'ASC' ) ? 'ASC' : 'DESC';

		$sql = "
			SELECT
				e.*,
				c.code AS category_code,
				c.title AS category_title,
				u.user_email AS email,
				fn.first_name,
				ln.last_name,
				s.state,
				p.phone
			FROM
				" . $wpdb->prefix . self::TABLE_NAME . " e
				JOIN " . $wpdb->prefix . Category::TABLE_NAME . " c
					ON e.category_id = c.id
				JOIN " . $wpdb->prefix . "users u
					ON e.photographer_id = u.ID
				LEFT JOIN
				(
					SELECT
						user_id,
						meta_value AS first_name
					FROM
						" . $wpdb->prefix . "usermeta
					WHERE
						meta_key = 'first_name'
				) fn ON u.ID = fn.user_id
				LEFT JOIN
				(
					SELECT
						user_id,
						meta_value AS last_name
					FROM
						" . $wpdb->prefix . "usermeta
					WHERE
						meta_key = 'last_name'
				) ln ON u.ID = ln.user_id
				LEFT JOIN
				(
					SELECT
						user_id,
						meta_value AS phone
					FROM
						" . $wpdb->prefix . "usermeta
					WHERE
						meta_key = 'phone'
				) p ON u.ID = p.user_id
				LEFT JOIN
				(
					SELECT
						user_id,
						meta_value AS state
					FROM
						" . $wpdb->prefix . "usermeta
					WHERE
						meta_key = 'state'
				) s ON u.ID = s.user_id
			WHERE
			    e.id > 0
			    " . ( ( $code !== NULL ) ? "AND random_code = " . $code : "" ) . "
			ORDER BY
			    e.is_finalist DESC,
				" . $sort . " " . $dir;

		$rows = $wpdb->get_results( $sql );
		foreach( $rows as $row )
		{
			$entry = new Entry;
			$entry->loadFromRow( $row );
			$entries[ $entry->getId() ] = $entry;
		}

		return $entries;
	}

	/**
	 * @return array
	 */
	public static function getAllRandomCodes()
	{
		global $wpdb;
		$codes = array();

		$sql = "
			SELECT
				random_code
			FROM
				" . $wpdb->prefix . self::TABLE_NAME . "
			WHERE
				random_code IS NOT NULL";

		$rows = $wpdb->get_results( $sql );
		foreach( $rows as $row )
		{
			$codes[] = $row->random_code;
		}

		return $codes;
	}

	/**
	 * @return $this
	 */
	public function assignRandomCode()
	{
		$codes = self::getAllRandomCodes();
		$code = NULL;
		$min = 1;
		$max = 9999;
		if ( count( $codes ) > 9999 )
		{
			$max = 99999;
		}

		while ( $code === NULL || in_array( $code, $codes ) )
		{
			$code = rand( $min, $max );
		}

		$this->setRandomCode( $code );

		return $this;
	}

	/**
	 * @param $number_of_entries
	 * @param $number_free_at
	 * @param $free_qty
	 *
	 * @return float|int
	 */
	public static function getFreeEntryCount( $number_of_entries, $number_free_at, $free_qty )
	{
		$free_entries = 0;
		
		if ( $number_free_at > 0 )
		{
			$free_instances = floor( $number_of_entries / $number_free_at );
			if ( $free_instances > 0 )
			{
				$free_entries += ( $free_instances * $free_qty );
			}
		}
		
		return $free_entries;
	}

	/**
	 * @param $number_of_entries
	 * @param $price_per_qty
	 *
	 * @return int
	 */
	public static function getPrice( $number_of_entries, $price_per_qty )
	{
	    return $price_per_qty * $number_of_entries;
	}

    /**
     * @param $code
     *
     * @return Entry[]
     */
	public static function getEntryByCode( $code )
    {
        return self::getAllEntries( 'e.id', 'DESC', $code );
    }
}