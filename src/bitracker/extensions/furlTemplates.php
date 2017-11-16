<?php
/**
 *  devCU Software Development
 *  devCU biTracker 1.0.0 Release
 *  Last Updated: $Date: 2014-07-13 09:01:45 -0500 (Sunday, 13 July 2014) $
 *
 * @author 		TG / PM
 * @copyright	(c) 2014 devCU Software Development
 * @Web	        http://www.devcu.com
 * @support       support@devcu.com
 * @license		 DCU Public License
 *
 * DevCU Public License DCUPL Rev 21
 * The use of this license is free for all those who choose to program under its guidelines. 
 * The creation, use, and distribution of software under the terms of this license is aimed at protecting the authors work. 
 * The license terms are for the free use and distribution of open source projects. 
 * The author agrees to allow other programmers to modify and improve, while keeping it free to use, the given software with the full knowledge of the original authors copyright.
 * 
 *  The full License is available at devcu.com
 *  http://www.devcu.com/devcu-public-license-dcupl/
 */

$_SEOTEMPLATES = array(
						'bitshowcat' => array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)showcat=(.+?)(&|$)/i', 'tracker/category/$2-#{__title__}/$3' ),
											'in'			=> array( 
																		'regex'		=> "#/tracker/category/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'showcat'	, '$1' )
																							)
																	) 
										),
						'bitshowfile' => array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)showfile=(.+?)(&|$)/i', 'tracker/file/$2-#{__title__}/$3' ),
											'in'			=> array( 
																		'regex'		=> "#/tracker/file/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'showfile'	, '$1' )
																							)
																	) 
										),
						'longwinded' => array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=display(&amp;|&)section=file(&amp;|&)id=(.+?)(&|$)/i', 'tracker/file/$4-#{__title__}/$5' ),
											'newTemplate'	  => 'bitshowfile',
											'in'			=> array( 
																		'regex'		=> "#/xxxyyyzzz/file/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'showfile'	, '$1' )
																							)
																	) 
										),
						'bittrack'		=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=confirm_download(&amp;|&)id=(.+?)(&|$)/i', 'tracker/download/$5-#{__title__}/$6' ),
											'in'			=> array( 
																		'regex'		=> "#/tracker/download/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'confirm_download' ),
																								array( 'id'			, '$1' )
																							)
																	) 
														),
						'bitdotrack'	=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=do_download(&amp;|&)id=(.+?)(&|$)/i', 'tracker/getdownload/$5-#{__title__}/$6' ),
											'in'			=> array( 
																		'regex'		=> "#/tracker/getdownload/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'do_download' ),
																								array( 'id'			, '$1' )
																							)
																	) 
														),
						'bitdd'		=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=confirm_download(&amp;|&)hash=(.+?)(&|$)/i', 'tracker/go/$5/#{__title__}' ),
											'in'			=> array( 
																		'regex'		=> "#/tracker/go/([a-zA-Z0-9]+?)/#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'confirm_download' ),
																								array( 'hash'		, '$1' )
																							)
																	) 
														),
						'bitdd2'	=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=do_download(&amp;|&)hash=(.+?)(&amp;|&)id=(\d+)(&|$)/i', 'tracker/get/$5/$7-#{__title__}' ),
											'in'			=> array( 
																		'regex'		=> "#/tracker/get/([a-zA-Z0-9]+?)/(\d+)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'do_download' ),
																								array( 'hash'		, '$1' ),
																								array( 'id'			, '$2' ),
																							)
																	) 
														),
						'bitbuy'	=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=buy(&amp;|&)id=(.+?)(&|$)/i', 'tracker/buy/$5-#{__title__}/$6' ),
											'in'			=> array( 
																		'regex'		=> "#/tracker/buy/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'buy' ),
																								array( 'id'			, '$1' )
																							)
																	) 
														),

						'bitann'	=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=client(&amp;|&)section=announce(&amp;|&)perm_key=(.+)(?|$)/i', 'announce-$4/$5' ),
											'in'			=> array( 
																		'regex'		=> "#^/announce-([0-9a-z]{32})(.+)#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'client' ),
																								array( 'section'	, 'announce' ),
									                                                            array( 'perm_key'	, '$1' )
																							)
																	) 
														),
						'bitscrape'	=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=client(&amp;|&)section=scrape(&amp;|&)perm_key=(.+)(?|$)/i', 'scrape-$4/$5' ),
											'in'			=> array( 
																		'regex'		=> "#^/scrape-([0-9a-z]{32})(.+)#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'client' ),
																								array( 'section'	, 'scrape' ),
									                                                            array( 'perm_key'	, '$1' )
																							)
																	) 
														),
						'bitannurl'	=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=client(&amp;|&)section=announce$/i', 'announce' ),
											'in'			=> array( 
																		'regex'		=> "#^/announce#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'client' ),
																								array( 'section'	, 'announce' )
																							)
																	) 
														),
						'bitscrapeurl'	=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker(&amp;|&)module=client(&amp;|&)section=scrape$/i', 'scrape' ),
											'in'			=> array( 
																		'regex'		=> "#^/scrape#i",
																		'matches'	=> array( 
																								array( 'app'		, 'bitracker' ),
																								array( 'module'		, 'client' ),
																								array( 'section'	, 'scrape' )
																							)
																	) 
														),

						'app=bitracker'		=> array( 
											'app'			=> 'bitracker',
											'allowRedirect' => 1,
											'out'			=> array( '/app=bitracker$/i', 'tracker/' ),
											'in'			=> array( 
																		'regex'		=> "#^/tracker#i",
																		'matches'	=> array( array( 'app', 'bitracker' ) )
																	) 
														),
					);